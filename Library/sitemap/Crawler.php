<?php
namespace SiteMap;

class Crawler
{
    /** Регулярное выражение для поиска ссылок */
    const LINK = "/<[Aa][^>]*[Hh][Rr][Ee][Ff]=['\"]?([^\"'>]+)[^>]*>/";

    /** @var string Ссылка из мета-тега base, если он есть на странице */
    private $base;

    /** @var array Массив проверенных ссылок */
    private $checked = array();

    /** @var array Массив для данных из конфига */
    public $config = array();

    /** @var string Переменная содержащая адрес главной страницы сайта */
    private $host;

    /** @var  array Массив НЕпроверенных ссылок */
    private $links = array();

    /** @var array Массив внешних ссылок */
    private $external = array();

    /** @var bool Флаг необходимости кэширования echo/print */
    public $ob = false;

    /** @var float Время начала работы скрипта */
    private $start;

    /** @var string Статус запуска скрипта. Варианты cron|test */
    public $status = 'cron';

    /** @var array Массив параметров curl для получения заголовков и html кода страниц */
    private $options = array(
        CURLOPT_RETURNTRANSFER => true, //  возвращать строку, а не выводить в браузере
        CURLOPT_VERBOSE => false, // вывод дополнительной информации (?)
        CURLOPT_HEADER => true, // включать заголовки в вывод
        CURLOPT_ENCODING => "", // декодировать запрос используя все возможные кодировки
        CURLOPT_AUTOREFERER => true, // автоматическая установка поля referer в запросах, перенаправленных Location
        CURLOPT_CONNECTTIMEOUT => 4, // кол-во секунд ожидания при соединении (мб лучше CURLOPT_CONNECTTIMEOUT_MS)
        CURLOPT_TIMEOUT => 4, // максимальное время выполнения функций cURL функций
        CURLOPT_FOLLOWLOCATION => false, // не идти за редиректами
        CURLOPT_MAXREDIRS => 0, // максимальное число редиректов
    );

    /**
     * Инициализация счетчика времени работы скрипта, вызов метода загрузки конфига,
     * определение хоста, вызов методов проверки существования карты сайта и загрузки временных
     * данных (при их наличии), запуск метода основного цикла скрипта.
     */
    public function __construct()
    {
        // Время начала работы скрипта
        $this->start = microtime(1);

        // Буферизация вывода нужна только для отправки сообщений при запуске через cron
        // При тестовых запусках вручную, скрипт обычно запускают из той же папки, где он лежит
        // При запусках через cron, скрипт никогда не запускается из той же папки
        $this->ob = !file_exists(basename($_SERVER['PHP_SELF']));

        // Проверяем статус запуска - тестовый или по расписанию
        if (isset($_GET['w']) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'w')) {
            // Если задан GET-параметр или ключ w в командной строке — это принудительный запуск,
            // письма о нём слать не надо
            $this->status = 'test';
            $this->ob = false;
        }
    }

    /**
     * Загрузка данных из конфига и из промежуточных файлов
     */
    public function loadData()
    {
        // Считываем настройки для создания карты сайта
        $this->loadConfig();

        // Установка максимального времени на загрузку страницы
        $this->options[CURLOPT_TIMEOUT] = $this->options[CURLOPT_CONNECTTIMEOUT] = $this->config['load_timeout'];

        // Проверка существования файла sitemap.xml и его даты
        $this->prepareSiteMapFile();

        // Проверка доступности и времени последнего сохранения промежуточного файла ссылок
        $this->checkTmpFile();

        // Загружаем данные, собранные на предыдущих шагах работы скрипта
        $this->loadParsedUrls();

        // Уточняем время, доступное для обхода ссылок $this->config['script_timeout']
        $this->setTimeout();

        if ((count($this->links) == 0) && (count($this->checked) == 0)) {
            // Если это самое начало сканирования, добавляем в массив для сканирования первую ссылку
            $this->links[$this->config['website']] = 0;
        }
    }

    /**
     * Вывод сообщения и завершение работы скрипта
     *
     * @param string $message - сообщение для вывода
     * @param bool $sendNotification - флаг обозначающий надобность отправления сообщения перед остановкой скрипта
     * @throws \Exception
     */
    protected function stop($message, $sendNotification = true)
    {
        if ($sendNotification) {
            $this->sendEmail($message);
        }
        throw new \Exception($message);
    }

    /**
     * Корректировка времени, в течение которого будут собираться ссылки
     */
    protected function setTimeout()
    {
        $count = count($this->links) + count($this->checked);
        if ($count > 1000) {
            $this->config['recording'] = ($count / 1000) * 0.05 + $this->config['recording'];
        }
        $this->config['script_timeout'] -= $this->config['recording'];
    }

    /**
     * Загрузка конфига в переменную $this->config
     */
    protected function loadConfig()
    {
        // Подгрузка конфига
        $config = __DIR__ . '/site_map.php';
        $message = 'Working with settings php-file from local directory';

        // Проверяем наличие файла рядом с запускаемым скриптом
        if (!file_exists($config)) {
            // Проверяем, есть ли конфигурационный файл в корневой папке Ideal CMS
            $config = substr(__DIR__, 0, stripos(__DIR__, '/Ideal/Library/sitemap')) . '/site_map.php';
            $message = 'Working with settings php-file from config directory';
            if (!file_exists($config)) {
                // Конфигурационный файл нигде не нашли :(
                $this->stop("Configuration file {$config} not found!");
            } else {
                // Если в корневой папке Ideal CMS есть конфигурационный файл карты сайта,
                // то подгружаем и основные файлы конфигурации.
                $configIdealCMS = substr(__DIR__, 0, stripos(__DIR__, '/Ideal/Library/sitemap')) . '/config.php';
                $siteDataIdealCMS = substr(__DIR__, 0, stripos(__DIR__, '/Ideal/Library/sitemap')) . '/site_data.php';
            }
        }

        echo $message . "\n";

        /** @noinspection PhpIncludeInspection */
        $this->config = require($config);

        if (isset($configIdealCMS)) {
            $configIdealCMS = require($configIdealCMS);
            if (!empty($configIdealCMS)) {
                $this->config['db_host'] = $configIdealCMS['db']['host'];
                $this->config['db_login'] = $configIdealCMS['db']['login'];
                $this->config['db_password'] = $configIdealCMS['db']['password'];
                $this->config['db_name'] = $configIdealCMS['db']['name'];
                $this->config['db_prefix'] = $configIdealCMS['db']['prefix'];

                // Ищем prev_structure для записи в Логи
                $leftPartPrevStructure = 0;
                $rightPartPrevStructure = 0;
                foreach ($configIdealCMS['structures'] as $structure) {
                    if ($structure['structure'] == 'Ideal_DataList') {
                        $leftPartPrevStructure = $structure['ID'];
                    }
                    if ($structure['structure'] == 'Ideal_Log') {
                        $rightPartPrevStructure = $structure['ID'];
                    }
                }
                if ($leftPartPrevStructure && $rightPartPrevStructure) {
                    $this->config['prev_structure'] = $leftPartPrevStructure . '-' . $rightPartPrevStructure;
                }
            }
            // Пытаемся определить идентификатор пользователя для записи в логи
            if (isset($siteDataIdealCMS)) {
                $siteDataIdealCMS = require($siteDataIdealCMS);
                if (!empty($siteDataIdealCMS)) {
                    if (session_id() == '') {
                        session_start();
                    }
                    if (isset($_SESSION[$siteDataIdealCMS['domain']])) {
                        $session = unserialize($_SESSION[$siteDataIdealCMS['domain']]);
                        $this->config['user_id'] = $session['user_data']['ID'];
                    }
                }
            }
        }

        if (!isset($this->config['existence_time_file'])) {
            $this->config['existence_time_file'] = 25;
        }

        $tmp = parse_url($this->config['website']);
        $this->host = $tmp['host'];
        if (!isset($tmp['path'])) {
            $tmp['path'] = '/';
        }
        $this->config['website'] = $tmp['scheme'] . '://' . $tmp['host'] . $tmp['path'];

        if (empty($this->config['pageroot'])) {
            if (empty($_SERVER['DOCUMENT_ROOT'])) {
                // Обнаружение корня сайта, если скрипт запускается из стандартного места в Ideal CMS
                $self = $_SERVER['PHP_SELF'];
                $path = substr($self, 0, strpos($self, 'Ideal') - 1);
                $this->config['pageroot'] = dirname($path);
            } else {
                $this->config['pageroot'] = $_SERVER['DOCUMENT_ROOT'];
            }
        }

        // Массив значений по умолчанию
        $default = array(
            'script_timeout' => 60,
            'load_timeout' => 10,
            'delay' => 1,
            'old_sitemap' => '/images/map-old.part',
            'tmp_file' => '/images/map.part',
            'pageroot' => '',
            'sitemap_file' => '/sitemap.xml',
            'crawler_url' => '/',
            'change_freq' => 'weekly',
            'priority' => 0.8,
            'time_format' => 'long',
            'disallow_key' => '',
            'disallow_regexp' => '',
            'seo_urls' => '',
        );
        foreach ($default as $key => $value) {
            if (!isset($this->config[$key])) {
                $this->config[$key] = $value;
            }
        }

        // Строим массивы для пропуска GET-параметров и URL по регулярным выражениям
        $this->config['disallow_key'] = explode("\n", $this->config['disallow_key']);
        $this->config['disallow_regexp'] = explode("\n", $this->config['disallow_regexp']);

        // Строим массив страниц с изменённым приоритетом
        $this->config['seo_urls'] = explode("\n", $this->config['seo_urls']);
        $seo = array();
        foreach ($this->config['seo_urls'] as $v => $k) {
            $a = explode('=', trim($k));
            $url = trim($a[0]);
            $priority = trim($a[1]);
            $seo[$url] = $priority;
        }
        $this->config['seo_urls'] = $seo;
    }

    /**
     * Проверка доступности и времени последнего сохранения промежуточного файла ссылок
     */
    protected function checkTmpFile()
    {
        $tmpFile = $this->config['pageroot'] . $this->config['tmp_file'];

        if (file_exists($tmpFile)) {
            if (!is_writable($tmpFile)) {
                $this->stop("Временный файл {$tmpFile} недоступен для записи!");
            }

            // Если промежуточный файл ссылок последний раз обновлялся более того количества часов назад,
            // которое указано в настройках, то производим его принудительную очистку.
            $existenceTimeFile = $this->config['existence_time_file'] * 60 * 60;
            if (time() - filemtime($tmpFile) > $existenceTimeFile) {
                file_put_contents($tmpFile, '');
            }

        } elseif ((file_put_contents($tmpFile, '') === false)) {
            // Файла нет и создать его не удалось
            $this->stop("Не удалось создать временный файл {$tmpFile} для карты сайта!");
        }
    }

    /**
     * Проверка наличия, доступности для записи и актуальности xml-файла карты сайта
     */
    protected function prepareSiteMapFile()
    {
        $xmlFile = $this->config['pageroot'] . $this->config['sitemap_file'];

        // Проверяем существует ли файл и доступен ли он для чтения и записи
        if (file_exists($xmlFile)) {
            if (!is_readable($xmlFile)) {
                $this->stop("File {$xmlFile} is not readable!");
            }
            if (!is_writable($xmlFile)) {
                $this->stop("File {$xmlFile} is not writable!");
            }
        } else {
            if ((file_put_contents($xmlFile, '') === false)) {
                // Файла нет и создать его не удалось
                $this->stop("Couldn't create file {$xmlFile}!");
            } else {
                // Удаляем пустой файл, т.к. пустого файла не должно быть
                unlink($xmlFile);
                return;
            }
        }

        // Проверяем, обновлялась ли сегодня карта сайта
        if (date('d:m:Y', filemtime($xmlFile)) == date('d:m:Y')) {
            if ($this->status == 'cron') {
                $this->stop("Sitemap {$xmlFile} already created today! Everything it's alright.", false);
            } else {
                // Если дата сегодняшняя, но запуск не из крона, то продолжаем работу над картой сайта
                echo "Warning! File {$xmlFile} have current date and skip in cron";
            }
        } else {
            // Если карта сайта в два раза старше указанного значения в поле "Максимальное время существования версии промежуточного файла",
            // то отсылаем соответствующее уведомление
            $countHourForNotify = $this->config['existence_time_file'] * 2;
            $existenceTimeFile = $countHourForNotify * 60 * 60;
            if (time() - filemtime($xmlFile) > $existenceTimeFile) {
                $this->sendEmail('Карта сайта последний раз обновлялась более ' . $countHourForNotify . ' часов(а) назад.');
            }
        }
    }

    /**
     * Метод для загрузки распарсенных данных из временного файла
     */
    protected function loadParsedUrls()
    {
        // Если существует файл хранения временных данных сканирования,
        // Данные разбиваются на 2 массива: пройденных и непройденных ссылок
        $tmpFile = $this->config['pageroot'] . $this->config['tmp_file'];
        if (file_exists($tmpFile)) {
            $arr = file_get_contents($tmpFile);
            $arr = unserialize($arr);

            $this->links = $arr[0];
            $this->checked = $arr[1];
            $this->external = $arr[2];
        }
    }

    /**
     * Метод для сохранения распарсенных данных во временный файл
     */
    protected function saveParsedUrls()
    {
        $result = array(
            $this->links,
            $this->checked,
            $this->external
        );

        $result = serialize($result);

        $tmp_file = $this->config['pageroot'] . $this->config['tmp_file'];

        $fp = fopen($tmp_file, 'w');

        fwrite($fp, $result);

        fclose($fp);
    }

    /**
     * Метод основного цикла для сборки карты сайта и парсинга товаров
     */
    public function run()
    {
        // Загружаем конфигурационные данные
        $this->loadData();

        // Список страниц, которые не удалось прочитать с первого раза
        $broken = array();

        /** Массив checked вида [ссылка] => пометка о том является ли ссылка корректной (1 - да, 0 - нет) */
        $number = count($this->checked) + 1;

        /** Массив links вида [ссылка] => пометка(не играет роли) */
        $time = microtime(1);
        while (count($this->links) > 0) {
            // Если текущее время минус время начала работы скрипта больше чем разница
            // заданного времени работы скрипта - завершаем работу скрипта
            if (($time - $this->start) > $this->config['script_timeout']) {
                break;
            }

            // Делаем паузу между чтением страниц
            usleep(intval($this->config['delay'] * 1000000));

            // Устанавливаем указатель на 1-й элемент
            reset($this->links);

            // Извлекаем ключ текущего элемента (то есть ссылку)
            $k = key($this->links);

            echo $number++ . '. ' . $k . "\n";

            /**
             * // handle lastmod
             * $res['lastmod'] = $lastmod;
             *
             * // format timestamp appropriate to settings
             * if ($res['lastmod'] != '') {
             * if ($this->config['time_format'] == 'short') {
             * $res['lastmod'] = $this->getDateTimeISO_short($res['lastmod']);
             * } else {
             * $res['lastmod'] = $this->getDateTimeISO($res['lastmod']);
             * }
             * }
             */

            // Получаем контент страницы
            $content = $this->getUrl($k, $this->links[$k]);

            // Парсим ссылки из контента
            $urls = $this->parseLinks($content);

            if (count($urls) < 10) {
                // Если мало ссылок на странице, значит что-то пошло не так и её нужно перечитать повторно
                if (isset($broken[$k])) {
                    // Если и при повторном чтении не удалось получить нормальную страницу, то останавливаемся
                    $this->stop("Сбой при чтении страницы {$k}\nПолучен следующий контент:\n{$content}");
                }
                $value = $this->links[$k];
                unset($this->links[$k]);
                $this->links[$k] = $broken[$k] = $value;
            }

            // Добавляем ссылки в массив $this->links
            $this->addLinks($urls, $k);

            // Добавляем текущую ссылку в массив пройденных ссылок
            $this->checked[$k] = 1;

            // И удаляем из массива непройденных
            unset($this->links[$k]);

            $time = microtime(1);
        }

        if (count($this->links) > 0) {
            $this->saveParsedUrls();
            $message = "\nВыход по таймауту\n"
                . 'Всего пройденных ссылок: ' . count($this->checked) . "\n"
                . 'Всего непройденных ссылок: ' . count($this->links) . "\n"
                . 'Затраченное время: ' . ($time - $this->start) . "\n\n"
                . "Everything it's alright.\n\n";
            $this->stop($message, false);
        }

        if (count($this->checked) < 2) {
            $this->sendEmail("Попытка записи в sitemap вместо списка ссылок:\n" . print_r($this->checked, true));
            $this->stop('В sitemap доступна только одна ссылка на запись');
        }

        $this->compare();

        $xmlFile = $this->saveSiteMap();

        $time = microtime(1);

        echo "\nSitemap successfuly created and saved to {$xmlFile}\n"
            . 'Count of pages: ' . count($this->checked) . "\n"
            . 'Time: ' . ($time - $this->start);
    }

    /**
     * Функция отправки сообщение с отчетом о создании карты сайта
     *
     * @param string $text Сообщение(отчет)
     * @param string $to Email того, кому отправить письмо
     */
    public function sendEmail($text, $to = '')
    {
        $header = "MIME-Version: 1.0\r\n"
            . "Content-type: text/plain; charset=utf-8\r\n"
            . 'From: sitemap@' . $this->host;

        $to = (empty($to)) ? $this->config['email_notify'] : $to;

        // Отправляем письма об изменениях
        mail($to, $this->host . ' sitemap', $text, $header);
    }

    /**
     * Записывает результат работы карты сайта в логи (в базу данных)
     *
     * @param string $text Сообщение(отчет)
     */
    public function saveToLog($text)
    {
        // Проверяем наличие всех необходимых данных для подключения к базе
        if (
            !empty($this->config['db_host']) &&
            !empty($this->config['db_login']) &&
            !empty($this->config['db_password']) &&
            !empty($this->config['db_name'])
        ) {
            // Подключаемся к базе данных
            $mysqli = new \mysqli($this->config['db_host'], $this->config['db_login'], $this->config['db_password'], $this->config['db_name']);
            if (!$mysqli->connect_error) {
                $mysqli->query('set character set utf8');
                $mysqli->query('set names utf8');
                $tableName = $this->config['db_prefix'] . 'ideal_structure_log';
                // Проверяем таблицу на существование
                $result = $mysqli->query("SHOW TABLES LIKE '{$tableName}';");
                if ($result->num_rows == 1) {
                    $time = time();
                    $userID = 0;
                    if (isset($this->config['user_id'])) {
                        $userID = $this->config['user_id'];
                    }
                    $prevStructure = '';
                    if (isset($this->config['prev_structure'])) {
                        $prevStructure = $this->config['prev_structure'];
                    }
                    // Вносим запись в таблицу
                    $sql = "INSERT INTO {$tableName}";
                    $sql .= ' (prev_structure,date_create,user_id,event_type,what_happened)';
                    $sql .= " VALUES ('{$prevStructure}',{$time},{$userID},'карта сайта', '{$text}')";
                    $mysqli->query($sql);
                }
                $mysqli->close();
            }

        }
    }

    /**
     * Преобразования специальных символов для xml файла карты сайта в HTML сущности
     *
     * @param string $str Ссылка для обработки
     * @return string Обработанная ссылка
     */
    public function xmlEscape($str)
    {
        $trans = array();
        if (!isset($trans)) {
            $trans = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
            foreach ($trans as $key => $value) {
                $trans[$key] = '&#' . ord($key) . ';';
            }
            // dont translate the '&' in case it is part of &xxx;
            $trans[chr(38)] = '&amp;'; // chr(38) = '&'
        }
        // Возвращается ссылка, в которой символы &,",<,>  заменены на HTML сущности
        return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};)/", "&#38;", strtr($str, $trans));
    }

    /**
     * Поиск изменений в новой карте сайта, относительно предыдущей
     */
    protected function compare()
    {
        $file = $this->config['pageroot'] . $this->config['old_sitemap'];
        $old = file_exists($file) ? unserialize(file_get_contents($file)) : '';

        $oldUrl = $old[0];
        $oldExternal = $old[1];

        $new = $this->checked;
        $external = $this->external;

        // Сохраним новый массив ссылок, что бы в следующий раз взять его как старый
        file_put_contents($file, serialize(array($new, $external)));

        $text = '';

        if (empty($oldUrl)) {
            $text = "Добавлены ссылки (первичная генерация карты)\n";
            foreach ($new as $k => $v) {
                $text .= $k;
                $text .= "\n";
            }
        } else {
            // Находим добавленные страницы
            $add = array_diff_key($new, $oldUrl);
            if (!empty($add)) {
                $text .= "Добавлены ссылки\n";
                foreach ($add as $k => $v) {
                    $text .= $k;
                    $text .= "\n";
                }
            } else {
                $text .= "Ничего не добавлено\n";
            }

            // Находим удаленные страницы
            $del = array_diff_key($oldUrl, $new);
            if (!empty($del)) {
                $text .= "Удалены ссылки \n";
                foreach ($del as $k => $v) {
                    $text .= $k;
                    $text .= "\n";
                }
            } else {
                $text .= "Ничего не удалено\n";
            }
        }

        if (empty($oldExternal)) {
            $text .= "\nДобавлены внешние ссылки(первичная генерация карты):\n";
            foreach ($external as $k => $v) {
                $text .= "{$k} на странице {$v}\n";
            }
        } else {
            // Определяем новые внешние ссылки
            $add = array_diff_key($external, $oldExternal);
            if (!empty($add)) {
                $text .= "\nДобавлены внешние ссылки:\n";
                foreach ($add as $k => $v) {
                    $text .= "{$k} на странице {$v}\n";
                }
            } else {
                $text .= "\nНет новых внешних ссылок\n";
            }

            $del = array_diff_key($oldExternal, $external);
            if (!empty($del)) {
                $text .= "\nУдалены внешние ссылки:\n";
                foreach ($del as $k => $v) {
                    $text .= "{$k} на странице {$v}\n";
                }
            } else {
                $text .= "\nНет удаленных внешних ссылок";
            }
        }

        $this->sendEmail($text);
        $this->saveToLog($text);
    }

    /**
     * Метод создания xml файла с картой сайта
     *
     * @return string Имя файла, в который сохраняется карта сайта
     */
    protected function saveSiteMap()
    {
        $lastDate = date('Y-m-d\TH:i:s') . substr(date("O"), 0, 3) . ":" . substr(date("O"), 3);

        $ret = '';
        foreach ($this->checked as $k => $v) {
            $ret .= '<url>';
            $ret .= sprintf('<loc>%s</loc>', $this->xmlEscape($k));
            // Временно без даты последнего изменения
            /*
            if (isset($url['lastmod'])) {
                if (is_numeric($url['lastmod'])) {
                    $ret[] = sprintf(
                        '<lastmod>%s</lastmod>',
                        $url['lastmod_dateonly'] ?
                        date('Y-m-d', $url['lastmod']):
                        date('Y-m-d\TH:i:s', $url['lastmod']) .
                        substr(date("O", $url['lastmod']), 0, 3) . ":" .
                        substr(date("O", $url['lastmod']), 3)
                    );
                } elseif (is_string($url['lastmod'])) {
                    $ret[] = sprintf('<lastmod>%s</lastmod>', $url['lastmod']);
                }
            }
            */
            if (isset($this->config['change_freq'])) {
                $ret .= sprintf(
                    '<changefreq>%s</changefreq>',
                    $this->config['change_freq']
                );
            }
            if (isset($this->config['priority'])) {
                $priorityStr = sprintf('<priority>%s</priority>', '%01.1f');
                if (isset($this->config['seo_urls'][$k])) {
                    $priority = $v;
                } else {
                    $priority = $this->config['priority'];
                }
                $ret .= sprintf($priorityStr, $priority);
            }
            $ret .= '</url>';
        }

        $ret = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
            >
    <!-- Last update of sitemap {$lastDate} -->
    {$ret}
    </urlset>
XML;

        $xmlFile = $this->config['pageroot'] . $this->config['sitemap_file'];
        $fp = fopen($xmlFile, 'w');
        fwrite($fp, $ret);
        fclose($fp);

        $tmp = $this->config['pageroot'] . $this->config['tmp_file'];
        unlink($tmp);

        return $xmlFile;
    }

    /**
     * Метод для получения html-кода страницы по адресу $k в основном цикле
     *
     * @param string $k Ссылка на страницу для получения её контента
     * @param string $place Страница, на которой получили ссылку (нужна только в случае ошибки)
     * @return string Html-код страницы
     */
    private function getUrl($k, $place)
    {
        $ch = curl_init($k);

        curl_setopt_array($ch, $this->options);

        $res = curl_exec($ch); // получаем html код страницы, включая заголовки

        $info = curl_getinfo($ch); // получаем информацию о запрошенной странице

        // Если страница недоступна прекращаем выполнение скрипта
        if ($info['http_code'] != 200) {
            $this->stop("Страница {$k} недоступна. Статус: {$info['http_code']}. Переход с {$place}");
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE); // получаем размер header'а

        curl_close($ch);

        $res = substr($res, $header_size); // вырезаем html код страницы

        return $res;
    }

    /**
     * Парсинг ссылок из обрабатываемой страницы
     *
     * @param string $content Обрабатываемая страницы
     * @return array Список полученных ссылок
     */
    protected function parseLinks($content)
    {
        // Получение всех ссылок со страницы
        preg_match_all(self::LINK, $content, $urls);

        return $urls[1];
    }

    /**
     * Обработка полученных ссылок, добавление в очередь новых ссылок
     *
     * @param array $urls Массив ссылок на обработку
     * @param string $current Текущая страница
     */
    private function addLinks($urls, $current)
    {
        foreach ($urls as $url) {
            // Убираем анкоры без ссылок
            if (strpos($url, '#') === 0) {
                continue;
            }

            if ($this->isExternalLink($url, $current)) {
                $this->external[$url] = $current;
                // Пропускаем ссылки на другие сайты
                continue;
            }

            // Абсолютизируем ссылку
            $link = $this->getAbsoluteUrl($url, $current);

            // Убираем лишние GET параметры из ссылки
            $link = $this->cutExcessGet($link);

            if ($this->skipUrl($link)) {
                // Если ссылку не нужно добавлять, переходим к следующей
                continue;
            }

            if (isset($this->links[$link]) || isset($this->checked[$link])) {
                // Пропускаем уже добавленные ссылки
                continue;
            }

            $this->links[$link] = $current;
        }
    }

    /**
     * Достраивание обрабатываемой ссылки до абсолютной
     *
     * @param string $link Обрабатываемая ссылка
     * @param string $current Текущая страница с которой получена ссылка
     * @return string  Возвращается абсолютная ссылка
     */
    protected function getAbsoluteUrl($link, $current)
    {
        // Закодированные амперсанды возвращаем к стандартному виду
        $link = str_replace('&amp;', '&', $link);

        // Раскодируем ссылку, чтобы привести её к единому формату хранения в списке
        $link = urldecode($link);

        $len = mb_strlen($link);
        if ($len > 1 && $link{$len - 1} == ' ') {
            // Если последний символ — пробел, то сообщаем об ошибке
            $this->stop("На странице {$current} неправильная ссылка, оканчивающаяся на пробел: '{$link}'");
        }

        if (substr($link, 0, 4) == 'http') {
            // Если ссылка начинается с http, то абсолютизировать её не надо
            $url = parse_url($link);
            if (empty($url['path'])) {
                // Если ссылка на главную и в ней отсутствует последний слеш, добавляем его
                $link .= '/';
            }
            return $link;
        }

        // Разбираем текущую ссылку на компоненты
        $url = parse_url($current);

        // Если последний символ в "path" текущей это слэш "/"
        if ($url['path']{mb_strlen($url['path']) - 1} == '/') {
            // Промежуточная директория равна "path" текущей ссылки без слэша
            $dir = substr($url['path'], 0, mb_strlen($url['path']) - 1);
        } else {
            // Устанавливаем родительский элемент
            $dir = dirname($url['path']);

            // Если в $dir - корень сайта, то он должен быть пустым
            $dir = (mb_strlen($dir) == 1) ? '' : $dir;

            // Если ссылка начинается с "?"
            if ($link{0} == '?') {
                // То обрабатываемая ссылка равна последней части текущей ссылки + сама ссылка
                $link = basename($url['path']) . $link;
            }
        }

        // Если ссылка начинается со слэша
        if ($link{0} == '/') {
            // Обрезаем слэш
            $link = substr($link, 1);
            // Убираем промежуточный родительский элемент
            $dir = '';
        }

        // Если ссылка начинается с "./"
        if (substr($link, 0, 2) == './') {
            $link = substr($link, 2);
        } else {
            // До тех пор пока ссылка начинается с "../"
            while (substr($link, 0, 3) == '../') {
                // Обрезаем "../"
                $link = substr($link, 3);
                // Устанавливаем родительскую директорию равную текущей, но обрезая её с последнего "/"
                $dir = substr($dir, 0, strrpos($dir, '/'));
            }
        }

        // Если задано base - добавляем его
        if (strlen($this->base)) {
            return $this->base . $link;
        }

        // Возвращаем абсолютную ссылку
        $result = $url['scheme'] . '://' . $url['host'] . $dir . '/' . $link;
        return $result;

    }

    /**
     * Проверка является ли ссылка внешней
     *
     * @param string $link Проверяемая ссылка
     * @param string $current Текущая страница с которой получена ссылка
     * @return boolean true если ссылка внешняя, иначе false
     */
    protected function isExternalLink($link, $current)
    {
        // Если ссылка на приложение - пропускаем её
        if (preg_match(',^(ftp://|mailto:|news:|javascript:|telnet:|callto:|tel:|skype:),i', $link)) {
            return true;
        }

        if (substr($link, 0, 4) != 'http') {
            // Если ссылка не начинается с http, то она точно не внешняя, все варианты мы исключили
            return false;
        }

        $url = parse_url($link);

        if ($this->host == $url['host']) {
            // Хост сайта и хост ссылки совпадают, значит она локальная
            return false;
        }

        if (str_replace('www.', '', $this->host) == str_replace('www.', '', $url['host'])) {
            // Хост сайта и хост ссылки не совпали, но с урезанием www совпали, значит неправильная ссылка
            $this->stop("Неправильная абсолютная ссылка: {$link} на странице {$current}");
        }

        return true;
    }

    /**
     * Метод для удаления ненужных GET параметров и якорей из ссылки
     *
     * @param string $url Обрабатываемая ссылка
     * @return string Возвращается ссылка без лишних GET параметров и якорей
     */
    protected function cutExcessGet($url)
    {
        $paramStart = strpos($url, '?');
        // Если существуют GET параметры у ссылки - проверяем их
        if ($paramStart !== false) {
            foreach ($this->config['disallow_key'] as $id => $key) {
                if ($key == '') {
                    continue;
                }
                // Разбиваем ссылку на части
                $link = parse_url($url);

                if (isset($link['query'])) {
                    // Разбиваем параметры
                    parse_str($link['query'], $parts);

                    foreach ($parts as $k => $v) {
                        // Если параметр есть в исключениях - удаляем его из массива
                        if ($k == $key) {
                            unset($parts[$k]);
                        }
                    }
                    // Собираем оставшиеся параметры в строку
                    $query = http_build_query($parts);
                    // Заменяем GET параметры оставшимися
                    $link['query'] = $query;

                    $url = $this->unparseUrl($link);
                }
            }
        }
        // Если в сслыке есть '#' якорь, то обрезаем его
        if (strpos($url, '#') !== false) {
            $url = substr($url, 0, strpos($url, '#'));
        }
        // Если последний символ в ссылке '&' - обрезаем его
        while (substr($url, strlen($url) - 1) == "&") {
            $url = rtrim($url, '&');
        }
        // Если последний символ в ссылке '?' - обрезаем его
        while (substr($url, strlen($url) - 1) == "?") {
            $url = rtrim($url, '?');
        }
        return $url;
    }

    /**
     * Создание ссылки из частей
     *
     * @param array $parsedUrl Массив полученный из функции parse_url
     * @return string Возвращается ссылка, собранная из элементов массива
     */
    protected function unparseUrl($parsedUrl)
    {
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user = isset($parsedUrl['user']) ? $parsedUrl['user'] : '';
        $pass = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Проверяем, нужно исключать этот URL или не надо
     * @param $filename
     * @return bool
     */
    protected function skipUrl($filename)
    {
        // Отрезаем доменную часть
        $filename = substr($filename, strpos($filename, '/') + 1);

        if (is_array($this->config['disallow_regexp']) && count($this->config['disallow_regexp']) > 0) {
            // Проходимся по массиву регулярных выражений. Если array_reduce вернёт саму ссылку,
            // то подходящего правила в disallow не нашлось и можно эту ссылку добавлять в карту сайта
            $tmp = $this->config['disallow_regexp'];
            $reduce = array_reduce(
                $tmp,
                function (&$res, $rule) {
                    if ($res == 1 || preg_match($rule, $res)) {
                        return 1;
                    }
                    return $res;
                },
                $filename
            );
            if ($filename !== $reduce) {
                // Сработало одно из регулярных выражений, значит ссылку нужно исключить
                return true;
            }
        }

        // Ни одно из правил не сработало, значит страницу исключать не надо
        return false;
    }

    /**
     * Примитивный mock-метод для доступа к закрытым методам для их тестирования
     *
     * @param string $methodName Название вызываемого метода класса
     * @param array $parameters Массив передаваемых методу параметров
     * @return mixed Результат работы метода
     */
    public function mock($methodName, $parameters)
    {
        $r = '';
        $str = '$r = $this->' . $methodName . '(' . implode(',', $parameters) . ');';
        eval($str);
        return $r;
    }

    /**
     * Метод для выполнения произвольного кода внутри класса в целях тестирования
     *
     * @param string $code Код, выполняемый внутри класса
     */
    public function evalMe($code)
    {
        eval($code);
    }
}
