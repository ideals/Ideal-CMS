<?php
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING); //| E_STRICT
ini_set('display_errors', 'On');

require_once(dirname(__FILE__) . '/Ontology.php');
require_once(dirname(__FILE__) . '/Crawler.class.php');
require_once(dirname(__FILE__) . '/GsgXml.class.php');

class myCrawler
{
    public $config; // конфигурация, считываемая из .ini-файла
    public $status = 'cron'; // статус запуска скрипта
    private $code; // код состояния после завершения работы паука
    public $url = array(); // список файлов(ссылок)

    /**
     * Считывание и инициализация настроек
     */
    function __construct()
    {
        // Запоминаем путь до скрипта от корня сайта
        $home = dirname($_SERVER['SCRIPT_FILENAME']);

        // Если запускается из консоли, берём путь из параметров
        if ($home == '') {
            $home = dirname($_SERVER['argv'][0]);
        }

        // Проверяем статус запуска - тестовый или по расписанию
        if ($_SERVER['argv'][1] == 'w' OR isset($_GET['w'])) {
            $this->status = 'test';
        }

        // Проверяем, есть ли ini-файл рядом со скриптом
        $iniFile = $home . '/sitemap.ini';
        $message = 'Working with ini-file from local directory';

        if (!file_exists($iniFile)) {
            // Проверяем, есть ли ini-файл в директории cms/config
            $iniFile = substr($home, 0, strpos($home, '/ns/_gpl/sitemap'))
                . '/config/sitemap.ini';
            $message = 'Working with ini-file from config directory';
            if (!file_exists($iniFile)) {
                // Ini-файла нигде не нашли :(
                $message = 'Ini-files not found. Path: ' . $iniFile;
                $iniFile = '';
            }
        }

        $this->info('', $message . "\n");

        if ($iniFile != '') {
            $this->config = parse_ini_file($iniFile, true);
            rtrim($this->config['website'], '/');
        } else {
            $this->code = 'error';
        }

        // Массив значений по умолчанию
        $default = array(
            "script_timeout" => 60,
            "load_timeout" => 10,
            "delay" => 1,
            "old_sitemap" => "/images/map-old.part",
            "tmp_file" => "/images/map.part",
            "sitemap_file" => "/sitemap.xml",
            "crawler_url" => "/",
            "change_freq" => "weekly",
            "priority" => 0.8,
            "time_format" => "long"
        );
        foreach ($default as $key => $value) {
            if (!isset($this->config[$key])) {
                $this->config[$key] = $value;
            }
        }
    }


    function checkUrl($checkUrl)
    {
        $urlToCrawl = $this->config['website'] . $checkUrl;

        $url = parse_url($urlToCrawl);
        $path = $url['path'];
        if ((substr($urlToCrawl, -1) != '/') && ($url['path'] == '')) {
            $path .= '/';
            $urlToCrawl .= '/';
        }

        // Running crawler engine from last point
        $crawler = new Crawler($urlToCrawl, $this->config['script_timeout'], $this->config['load_timeout'], $this->config['delay']);
        $res = $crawler->_getFilesForURL(array($urlToCrawl, ''));

        return $crawler->todo;
    }

    /**
     * Запуск паука
     * @return array список всех страниц сайта
     */
    function runCrawler()
    {

        $urlToCrawl = $this->config['website'] . $this->config['crawler_url'];

        $url = parse_url($urlToCrawl);
        $path = $url['path'];
        if ((substr($urlToCrawl, -1) != '/') && ($url['path'] == '')) {
            $path .= '/';
            $urlToCrawl .= '/';
        }

        // TODO check if we have a already started scan

        // Running crawler engine from last point
        $crawler = new Crawler($urlToCrawl, $this->config['script_timeout'], $this->config['load_timeout'], $this->config['delay']);

        // Если существует файл хранения временных данных сканирования,
        // Считываем их и запускаем сканирование с последней точки
        // Если нет - запускаем как обычно
        $tmpFile = $this->config['pageroot'] . $this->config['tmp_file'];
        if (file_exists($tmpFile)) {
            $this->info('', 'Продолжаем сканирование с точки');
            $SETTINGS = unserialize(file_get_contents($tmpFile));
            unlink($tmpFile);
            $crawler->setDone($SETTINGS[timeout_done]);
            $crawler->setFiles($SETTINGS[timeout_file]);
            $crawler->setTodo($SETTINGS[timeout_todo]);
            $crawler->beforeTimeout = $SETTINGS['beforeTimeout'];
        } else {
            $crawler->beforeTimeout = 1;
            $crawler->setDone($SETTINGS[PSNG_TIMEOUT_DONE]);
            $crawler->setFiles($SETTINGS[PSNG_TIMEOUT_FILE]);
        }
        $crawler->setForbiddenKeys($this->config['disallow_key']);
        $crawler->setForbiddenDirectories($this->config['disallow_dir']);
        $crawler->setForbiddenFiles($this->config['disallow_file']);
        $crawler->setForbiddenPages($this->config['disallowPage']);

        //Set the directory to forbid the crawler to follow below it
        $crawler->setDirectory($path);

        $rcs = $crawler->start();
        if ($rcs === false) {
            $this->code = $crawler->errType;
            return false;
        }
        if (!$crawler->hasFinished()) {
            // store current data into session
            $SETTINGS[PSNG_TIMEOUT_TODO] = $crawler->getTodo();
            $SETTINGS[PSNG_TIMEOUT_DONE] = $crawler->getDone();
            $SETTINGS[PSNG_TIMEOUT_FILE] = $crawler->getFiles();
            $SETTINGS[PSNG_TIMEOUT_ACTION] = PSNG_TIMEOUT_ACTION_WEBSITE;
            $SETTINGS['beforeTimeout'] = $crawler->beforeTimeout;
            $this->code = 'timeout';
            // Записываем текущие результаты в файл
            $tmpFile = $this->config['pageroot'] . $this->config['tmp_file'];
            $result = file_put_contents($tmpFile, serialize($SETTINGS));
            if (!$result) {
                $this->info('', 'TMP file not found: ' . $tmpFile);
            }
            return false;
        } else {
            while ($crawler->hasNext()) {
                $fileinfo = $crawler->getNext(); // returns an array

                if (!isset($fileinfo['http_status'])) $fileinfo['http_status'] = '';
                if (!isset($fileinfo['file'])) $fileinfo['file'] = '';
                if (!isset($fileinfo['lastmod'])) $fileinfo['lastmod'] = '';
                if (!isset($fileinfo['changefreq'])) $fileinfo['changefreq'] = '';
                if (!isset($fileinfo['priority'])) $fileinfo['priority'] = '';

                $http_status = $fileinfo['http_status'];
                // create and setup valid values
                $fileinfo = $this->handleURL($fileinfo['file'], $fileinfo['lastmod'], $fileinfo['changefreq'], $fileinfo['priority']);

                //$fileinfo = handleURLCached($FILES_CACHE, $fileinfo);

                // handle some website specific stuff
                if ($http_status == '404') {
                    $fileinfo['file_enabled'] = '';
                    $fileinfo['http_status'] = 'class="notfound"';
                }

                $FILE[$fileinfo['file_url']] = $fileinfo;

                // Создаем список ссылок
                array_push($this->url, $fileinfo['file_url']);
            }
            $SETTINGS[PSNG_TIMEOUT_ACTION] = '';
            if(count($this->url) == 1){
                $this->code = 'onePage';
                return false;
            }
            $this->compare();
        }

        return $FILE;
    }


    /**
     * returns a correct entry for a fileinfo with given information and settings
     */
    function handleURL($url, $lastmod = '', $changefreq = '', $priority = '')
    {
        $res = array();

        $res['file_url'] = $url;

        // default: file is enabled and will be handled
        $res['file_enabled'] = 'checked';

        // handle lastmod
        $res['lastmod'] = $lastmod;

        // format timestamp appropriate to settings
        if ($res['lastmod'] != '') {
            if ($this->config['time_format'] == 'short') {
                $res['lastmod'] = $this->getDateTimeISO_short($res['lastmod']);
            } else {
                $res['lastmod'] = $this->getDateTimeISO($res['lastmod']);
            }
        }

        // handle changefreq
        // $changefreq - никак не используется, т.к. в классе паука она определяется неправильно
        $res['changefreq'] = $this->getFrequency($lastmod);


        // handle priority
        if ($this->config['priority'] != '') {
            $res['priority'] = $this->getPriority($url);
        }

        return $res;
    }


    /**
     * checks, if there is an entry in filelist cache;
     *  if yes, update fileinformation;
     *        returns fileinformation (given or updated)
     */
    function handleURLCached($FILES_CACHE, $fileInfo)
    {
        global $SETTINGS;
        $filename = $fileInfo[PSNG_FILE_URL];

        if ((isset($FILES_CACHE)) && (isset($FILES_CACHE[$filename]) != '') && ($FILES_CACHE[$filename] != '')) {
            $fileInfo[PSNG_FILE_ENABLED] = $FILES_CACHE[$filename][PSNG_FILE_ENABLED];
            if (isset($FILES_CACHE[$filename][PSNG_CHANGEFREQ]) && ($FILES_CACHE[$filename][PSNG_CHANGEFREQ] != '')) {
                $fileInfo[PSNG_CHANGEFREQ] = $FILES_CACHE[$filename][PSNG_CHANGEFREQ];
            }

            if (isset($FILES_CACHE[$filename][PSNG_PRIORITY]) && ($FILES_CACHE[$filename][PSNG_PRIORITY] != '')) {
                $fileInfo[PSNG_PRIORITY] = $FILES_CACHE[$filename][PSNG_PRIORITY];
            }

            $fileInfo[PSNG_HTML_HISTORY] = 'class="history"';
        }

        return $fileInfo;
    }


    function debug($param, $msg)
    {
        return;
        //echo "\n{$param}\n{$msg}";
    }

    function info($param, $msg = '')
    {
        echo "\n{$param}\n{$msg}\n";
    }

    function compare()
    {
        $file = $this->config['pageroot'] . $this->config['old_sitemap'];

        $new = $this->url;
        $old = unserialize(file_get_contents($file));
        $text = "";
        file_put_contents($file, serialize($new)); // сохраним новый массив ссылок, что бы в следующий раз взять его как старый


        if (empty($old)) {
            $text = "Добавлены ссылки(первичная генерация карты) \n";
            foreach ($new as $v) {
                $text .= $v;
                $text .= "\n";
            }

        } else {

            // Находим добавленные и удаленные страницы
            $del = array_diff($old, $new);
            $add = array_diff($new, $old);

            if (!empty($add)) {
                $text .= "Добавлены ссылки \n";
                foreach ($add as $v) {
                    $text .= $v;
                    $text .= "\n";
                }
            } else {
                $text .= "Ничего не добавлено\n";
            }
            if (!empty($del)) {
                $text .= "Удалены ссылки \n";
                foreach ($del as $v) {
                    $text .= $v;
                    $text .= "\n";
                }
            } else {
                $text .= "Ничего не удалено\n";
            }
        }
        $url = parse_url($this->config['website']);
        if (substr($url['host'], 0, 4) == 'www.') $url['host'] = substr($url['host'], 4);
        $from = 'From: sitemap@' . $url['host'];

        // Отправляем письма об изменениях
        foreach ($this->config['email'] as $mail) {
            mail($mail, $this->config['website'], $text, $from);
        }
    }


    /**
     * writes sitemap to file
     */
    function writeSitemap($FILE)
    {
        global $SETTINGS, $openFile_error, $LAYOUT;

        $gsg = new GsgXml($this->config['website'] . '/');

        $numb = 0;
        $txtfilehandle = null;

        foreach ($FILE as $numb => $value) {
            if ($value[PSNG_FILE_ENABLED] != '') {
                $this->debug($value, "Adding file " . $value[PSNG_FILE_URL]);
                if (isset($txtfilehandle)) fputs($txtfilehandle, $value[PSNG_FILE_URL] . "\n");
                if ($gsg->addUrl($value['file_url'], false, $value['lastmod'], false, $value['changefreq'], $value['priority']) === false) {
                    $this->info($value[PSNG_FILE_URL], 'Could not add file to sitemap' . $gsg->errorMsg);
                }
            } else {
                $this->debug($value[PSNG_FILE_URL], 'Not enabled, so not writing file to sitemap');
            }
        }

        $sitemap_file = $this->config['pageroot'] . $this->config['sitemap_file'];
        $filehandle = $this->openFile($sitemap_file, true);
        if ($filehandle === false) {
            $this->info($openFile_error, 'Could not write sitemap');
            return false;
        }
        $xml = $gsg->output(true, $SETTINGS[PSNG_COMPRESS_SITEMAP], false);

        fputs($filehandle, $xml);
        fclose($filehandle);
        if (isset($txtfilehandle)) fclose($txtfilehandle);

        if ($numb > 50000) {
            $this->info('Not implemented: split result into files with only 50000 entries', 'Only 50000 entries are allowed in one sitemap file at the moment!');
        }
        $sitemap_url = $this->config['website'] . $this->config['sitemap_file'];
        $this->info('Sitemap successfuly created and saved to ' . $sitemap_url . '.', 'Count of pages: ' . count($FILE));


        return true;
    }

    /**
     * Проверка наличия, доступности записи и необходимости создавать в этот день карту сайта
     */
    function checkXmlFile()
    {
        $xmlFile = $this->config['pageroot'] . $this->config['sitemap_file'];

        // Проверяем существует ли файл и доступен ли он для чтения и записи
        if (file_exists($xmlFile)) {
            if (!is_readable($xmlFile)) {
                $this->info('', "File {$xmlFile} is not readable");
                return false;
            }
            if ($writable && !is_writable($xmlFile)) {
                $this->info('', "File {$xmlFile} is not writable");
                return false;
            }
        }

        // Проверяем, обновлялась ли сегодня карта сайта
        if (date('d:m:Y', filemtime($xmlFile)) == date('d:m:Y')) {
            if ($this->status == 'cron') {
                echo 'empty';
                return false; // TODO обработка времени файла
            } else {
                echo "Warning! File {$xmlFile} have current date and skip in cron";
            }
        }

        return true;
    }


    /**
     * returns a filehandle if file is accessable
     */
    function openFile($filename, $writable = false)
    {
        global $openFile_error;
        $openFile_error = "";
        // check if file exists - if yes, perform tests:
        if (file_exists($filename)) {
            // check if file is accessable
            if (!is_readable($filename)) {
                $openFile_error = "File $filename is not readable";
                return false;
            }
            if ($writable && !is_writable($filename)) {
                $openFile_error = "File $filename is not writable";
                return false;
            }
        } else {
            // file does not exist, try to create file
        }
        $accessLevel = 'r+';
        if ($writable === true) {
            $accessLevel = 'w+';
        }

        // Проверяем, можно ли записывать в xml-файл
        $filehandle = @fopen($filename, $accessLevel);
        if ($filehandle === false) {
            $openFile_error = "File $filename could not be opened, don't know why";
            @fclose($filehandle);

            if (!file_exists($filename)) {
                $openFile_error = "File $filename does not exist and I do not have the rights to create it!";
            }
            return false;
        }
        return $filehandle;
    }

    public function run()
    {
        if ($this->code != '') {
            return $this->code;
        }

        if (!$this->checkXmlFile()) {
            return 'error';
        }

        $file = $this->runCrawler();

        if ($file !== false) {
            $this->writeSitemap($file);
        } else {
            switch ($this->code) {
                case 'timeout':
                    $this->info('', 'Выход по таймауту');
                    break;
                case 'done':
                    break;
                case 'onePage':
                    $from = "From: sitemap@".$this->config['website'];
                    $this->info('', 'В sitemap доступна только одна ссылка на запись');
                    mail('help1@neox.ru, top@neox.ru', $this->config['website'],
                        'Попытка записи только одной страницы в sitemap', $from);
                    break;
                case '404':
                    $this->info('', 'Страница не найдена');
                    $sitemap_file = $this->config['pageroot'] . $this->config['sitemap_file'];
                    $file = file_get_contents($sitemap_file);
                    unlink($sitemap_file);
                    file_put_contents($sitemap_file, $file);
                default:
                    $this->info('', 'Webserver has an error. Shutting down');
                    break;
            }
        }
        return $this->code;
    }

    function getFrequency($lastmod)
    {
        // set changefreq
        $cf_config = $this->config['change_freq'];

        if ($cf_config != 'dynamic') {
            return $cf_config;
        }

        $age = time() - $lastmod;
        $change_freq = "monthly"; // default value
        if ($age < 10) {
            $change_freq = "always";
        } elseif ($age < 60 * 60) {
            $change_freq = "hourly";
        } elseif ($age < 60 * 60 * 24) {
            $change_freq = "daily";
        } elseif ($age < 60 * 60 * 24 * 7) {
            $change_freq = "weekly";
        } elseif ($age < 60 * 60 * 24 * 31) { // longest month has 31 days
            $change_freq = "monthly";
        } elseif ($age < 60 * 60 * 24 * 365) {
            $change_freq = "yearly";
        } else {
            $change_freq = "never";
        }
        return $change_freq;
    }

    function getPriority($url)
    {
        // Назначаем приоритет по умолчанию
        $priority = $this->config['priority'];

        // Главной странице назначаем 1
        if ($url == $this->config['website'] . '/') {
            $priority = 1;
        }

        // Сео-ссылки со своими приоритетами
        if (isset($this->config['seo_urls'][$url])) {
            $priority = $this->config['seo_urls'][$url];
        }

        return $priority;
    }

    function getDateTimeISO($timestamp)
    {
        return date("Y-m-d\TH:i:s", $timestamp) . substr(date("O"), 0, 3) . ":" . substr(date("O"), 3);
    }

    function getDateTimeISO_short($timestamp)
    {
        return date("Y-m-d", $timestamp);
    }

}

$sitemap = new myCrawler();


// Проверяем, нужно ли кешировать вывод для ручной отправки письма с результатами
$manualSend = 0;

if ($sitemap->status == 'cron' AND $sitemap->config['sendmail']['send']) {
    $manualSend = 1;
    ob_start();
}

echo '<pre>';

if (isset($_GET['url'])) {
    $links = $sitemap->checkUrl($_GET['url']);
    print_r($links);
} else {
    // Если никаких доп. параметров не задано - запускаем построение карты сайта
    $sitemap->run();
}

echo '</pre>';

if ($manualSend) {
    $text = ob_get_contents();
    ob_end_clean();
    mail($sitemap->config['sendmail']['address'],
        str_replace('http://', '', $sitemap->config['website']) . ' sitemap',
        $text,
        "From: Sitemap Maker <sitemap@" . str_replace('http://', '', $sitemap->config['website']) . ">\r\n");
    echo $text;
}

