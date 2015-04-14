<?php
namespace ParseIt;

header('Content-Type: text/html; charset=utf-8');

class ParseIt
{
    /** Регулярное выражение для поиска ссылок */
    const LINK = "/<[Aa][^>]*[Hh][Rr][Ee][Ff]=['\"]?([^\"'>]+)[^>]*>/";

    /** @var float Время начала работы скрипта */
    private $start;

    /** @var string Переменная содержащая адрес главной страницы сайты */
    private $host;

    /** @var  array Массив НЕпроверенных ссылок */
    private $links = array();

    /** @var array Массив проверенных ссылок */
    private $checked = array();

    /** @var array Массив для данных из конфига */
    private $config = array();

    /** @var boolean Булева переменная: ссылки с www или без */
    private $withWWW;

    /** @var string Перменная для base */
    private $base;

    private $options = array(
        CURLOPT_RETURNTRANSFER => true, //  возвращать строку, а не выводить в браузере
        CURLOPT_VERBOSE => true, // вывод дополнительной информации (?)
        CURLOPT_HEADER => true, // включать заголовки в вывод
        CURLOPT_FOLLOWLOCATION => true, // следовать по любому заголовку Location
        CURLOPT_ENCODING => "", // декодировать запрос используя все возможные кодировки
        CURLOPT_AUTOREFERER => true, // автоматическая установка поля referer в запросах, перенаправленных Location
        CURLOPT_CONNECTTIMEOUT => 4, // кол-во секунд ожидания при соединении (мб лучше CURLOPT_CONNECTTIMEOUT_MS)
        CURLOPT_TIMEOUT => 4, // максимальное время выполнения функций cURL функций
        CURLOPT_MAXREDIRS => 10, // максимальное число редиректов
    );

    public function __construct()
    {
        // Время начала работы скрипта
        $this->start = microtime(1);

        $this->loadConfig();

        $tmp = $this->config['website'];
        $tmp = parse_url($tmp);
        $this->host = $tmp['host'];

        // Проверка существования файла sitemap.xml и его даты
        if ($this->isSiteMapExist()) {
            $this->stop('Карта сайта уже создана');
        }
        //list($this->links, $this->checked) = $this->loadParsedUrls();
        $this->loadParsedUrls();

        if ((count($this->links) == 0) && (count($this->checked) == 0)) {
            // Если это самое начало сканирования, добавляем в массив для сканирования первую ссылку
            $this->links[$this->config['website']] = 0;
        }

        $this->run();
    }

    /**
     * Вывод ошибки и завершение работы скрипта
     * @param string $message - сообщение для вывода
     */
    protected function stop($message)
    {
        echo $message;
        exit();
    }

    /** Загрузка конфига */
    protected function loadConfig()
    {
        // Подгрузка конфига
        $config = __DIR__ . "/sitemap.php";

        if (!file_exists($config)) {
            $this->stop("Конфигурационный файл {$config} не найден!");
        } else {
            $this->config = require($config);
            $this->config['website'] = rtrim($this->config['website'], '/');
            $tmp = parse_url($this->config['website']);
            $this->host = $tmp['host'];

            if (strpos($this->host, 'www') === 0) {
                $this->withWWW = true;
            } else {
                $this->withWWW = false;
            }

            if (isset($this->config['seo_urls'])) {
                $seo = array();
                $tmp = explode(',', $this->config['seo_urls']);
                foreach ($tmp as $v => $k) {
                    $a = explode('=', trim($k));
                    $url = trim($a[0]);
                    $priority = trim($a[1]);
                    $seo[$url] = $priority;
                }
                $this->config['seo_urls'] = $seo;

            }
        }
    }

    /** Проверка существования xml файла карты сайта */
    protected function isSiteMapExist()
    {
        $xmlFile = $this->config['pageroot'] . $this->config['sitemap_file'];

        // Проверяем существует ли файл и доступен ли он для чтения и записи
        if (file_exists($xmlFile)) {
            if (!is_readable($xmlFile)) {
                $this->stop("Карта сайта {$xmlFile} не доступна для чтения!");
            }
            if (!is_writable($xmlFile)) {
                $this->stop("Карта сайта {$xmlFile} не доступна для записи!");
            }
            // Проверяем, обновлялась ли сегодня карта сайта
            if (date('d:m:Y', filemtime($xmlFile)) == date('d:m:Y')) {
                $this->stop("Карта сайта {$xmlFile} уже создавалась сегодня!");
            }
            return true;
        } else {
            // Файла нет, пытаемся создать
            if (file_put_contents($xmlFile, '') === false) {
                // Создать файл не получилось
                $this->stop("Не удалось создать файл {$xmlFile} для карты сайта!");
            }
            return false;
        }

    }

    /** Метод для загрузки распарсенных данных из временного файла */
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

        }
    }

    /** Метод для сохранения распарсенных данных во временный файл */
    protected function saveParsedUrls()
    {
        $result[0] = $this->links;
        $result[1] = $this->checked;

        $result = serialize($result);

        $tmp_file = __DIR__ . $this->config['tmp_file'];

        $fp = fopen($tmp_file, "w");

        fwrite($fp, $result);

        fclose($fp);
    }

    /** Метод основного цикла для сборка карты сайта и парсинга товаров */
    private function run()
    {
        /** Массив links вида [ссылка] => пометка(не играет роли) */
        /** Массив checked вида [ссылка] => пометка о том является ли ссылка корректной (1 - да, 0 - нет) */
        while (count($this->links) > 0) {
            $time = microtime(1);
            /** todo переделать условие на нормальное,
             * зависящее от времени выполнения скрипта и времени на сохранение данных
             */
            if (($time - $this->start) > 56.00) {
                break;
            }

            // Устанавливаем указатель на 1-й элемент
            reset($this->links);

            // Извлекаем ключ текущего элемента (то есть ссылку)
            $k = key($this->links);

            // Получаем контент страницы
            $content = $this->getUrl($k, $this->links[$k]);

            // Парсим ссылки из контента
            $urls = $this->parseLinks($content);

            // Добавляем ссылки в массив $this->links
            $this->addLinks($urls, $k);

            // Добавляем текущую ссылку в массив пройденных ссылок
            $this->checked[$k] = 1;

            // И удаляем из массива непройденных
            unset ($this->links[$k]);

        }

        if (count($this->links) > 0) {
            $this->saveParsedUrls();
            echo 'Всего пройденных ссылок: ' . count($this->checked) . "\n" . '</br>';
            echo 'Всего непройденных ссылок: ' . count($this->links) . '</br>';
            echo($time - $this->start);
            echo "<pre>";
            print_r($this->checked);
            print_r($this->links);
            echo "</pre>";
            exit();
        }
        $this->saveSiteMap();
    }

    /**
     * Преобразования специальных символов для xml файла карты сайта
     * @param string $str - ссылка для обработки
     * @return string - обработанная ссылка
     */
    public function xmlEscape($str)
    {
        static $trans;
        if (!isset($trans)) {
            $trans = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
            foreach ($trans as $key => $value) {
                $trans[$key] = '&#' . ord($key) . ';';
            }
            // dont translate the '&' in case it is part of &xxx;
            $trans[chr(38)] = '&amp;';
        }
        return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};)/", "&#38;", strtr($str, $trans));
    }

    /** Метод создания xml файла с картой сайта */
    protected function saveSiteMap()
    {
        $ret = array();

        $ret[] = sprintf('<?xml version="1.0" encoding="%s"?>', 'UTF-8');
        $ret[] = sprintf(
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'
        );
        $ret[] = sprintf(
            '<!-- Last update of sitemap %s -->',
            date('Y-m-d\TH:i:s') . substr(date("O"), 0, 3) . ":" . substr(date("O"), 3)
        );

        foreach ($this->checked as $k => $v) {
            $ret[] = '<url>';
            $ret[] = sprintf('<loc>%s</loc>', $this->xmlEscape($k));
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
                $ret[] = sprintf(
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
                $ret[] = sprintf($priorityStr, $priority);
            }
            $ret[] = '</url>';
        }

        $ret[] = '</urlset>';

        $xmlFile = $this->config['pageroot'] . $this->config['sitemap_file'];
        $fp = fopen($xmlFile, 'w');
        foreach ($ret as $v) {
            fwrite($fp, $v);
        }
        fclose($fp);

        //echo "Карта сайта успешно создана!\n";
        $time = microtime(1);
        echo 'done ' . ($time - $this->start);
    }

    /**
     * Метод для получения html кода и парсинга текущей страницы в основном цикле
     * @param string $k - получение html кода из ссылки
     * @param string $place - страница, на которой получили ссылку (нужна только в случае ощибки)
     * @return string  - возвращается строка с html кодом
     */
    private function getUrl($k, $place)
    {
        $ch = curl_init($k);

        curl_setopt_array($ch, $this->options);

        $res = curl_exec($ch); //получаем html код страницы, включая заголовки

        $info = curl_getinfo($ch); // получаем инофрмацию о запрошенной странице

        // Если страница недоступна прекращаем выполнение скрипта
        if ($info['http_code'] >= '400' && $info < '599') {
            $this->stop('Страница' . $k . 'недоступна. Ошибка' . $info['http_code'] . ". Переход с $place");
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE); // получаем размер header'а

        curl_close($ch);

        $res = substr($res, $header_size); //вырезаем html код страницы

        return $res;
    }


    /**
     * Парсинг ссылок из обрабатываемой страницы
     * @param string $content - Обрабатываемая страницы
     * @return array - Список полученных ссылок
     */
    protected function parseLinks($content)
    {
        // Получение всех ссылок со страницы
        preg_match_all(self::LINK, $content, $urls);

        return $urls[1];
    }

    /**
     * Обработка полученных ссылок, добавление в очередь новых ссылок
     * @param array $urls - массив ссылок на обработку
     * @param string $current - текущая страница
     */
    private function addLinks($urls, $current)
    {
        foreach ($urls as $url) {
            //Убираем все флаги без ссылок
            if (strpos($url, '#') === 0) {
                continue;
            }

            $link = $this->getAbsoluteUrl($url, $current);

            if ($this->isExternalLink($link)) {
                // Пропускаем ссылки на другие сайты
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
     * @param string $link - обрабатываемая ссылка
     * @param string $current - текущая страница с которой получена ссылка
     * @return string - возвращается абсолютная ссылка
     */
    protected function getAbsoluteUrl($link, $current)
    {
        if (preg_match(',^(https?://|ftp://|mailto:|news:|javascript:|telnet:|callto:|skype:),i', $link)) {
            // hostname is not the same (with/without www) than the one used in the link
            // Если ссылка начинается с http
            if (substr($link, 0, 4) == 'http') {
                // То разбиваем её на части
                $url = parse_url($link);
                // Если хост из данной ссылке не равен заданному хосту,
                // но они равны при добавлении к одному из них www
                if ($url['host'] != $this->host && ((("www." . $url['host']) == $this->host) &&
                     $this->withWWW == true || ($url['host'] == ("www." . $this->host)) && $this->withWWW == false)) {
                    // заменяем хост из ссылки заданным хостом (из конфига)
                    $link = str_replace($url['host'], $this->host, $link);
                }
                // Если передан только хост, то добавляем в конце "/"
                if (!array_key_exists('path', $url) || $url['path'] == '' && substr($link, -1) != '/') {
                    $link .= '/';
                }
            }
            return $link;
        }

        // Разбираем текущую ссылку на компоненты
        $url = parse_url($current);

        // Если последний символ в "path" текущей это слэш "/"
        if ($url['path']{strlen($url['path']) - 1} == '/') {
            // Промежуточная директория равна "path" текущей ссылки без слэша
            $dir = substr($url['path'], 0, strlen($url['path']) - 1);
        } else {
            // Устанавливаем родительский элемент
            $dir = dirname($url['path']);
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

        // Если выбранный хост из ссылки не равен хосту из конфига,
        // но он все же есть в обрабатываемой ссылке, то задаем хост из конфига как текущий.
        if ($url['host'] != $this->host &&
            (strpos($url['host'], $this->host) != false || strpos($this->host, $url['host']) != false)) {
            $url['host'] = $this->host;
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

        // Если задано base -  добваляем его
        if (strlen($this->base)) {
            return $this->base . urldecode($link);
        }

        // Возвращаем абсолютную ссылку
        return sprintf('%s://%s%s/%s', $url['scheme'], $url['host'], $dir, urldecode($link));

    }

    /**
     * Проверка является ли ссылка внешней
     * @param string $link - проверяемая ссылка
     * @return boolean - true если ссылка внешняя, иначе false
     */
    protected function isExternalLink($link)
    {
        // Если ссылка на приложение - пропускаем её
        if (preg_match(',^(ftp://|mailto:|news:|javascript:|telnet:|callto:|skype:),i', $link)) {
            return true;
        }

        $url = parse_url($link);

        //Начальная директория - хост из конфига
        $startDir = $this->host;
        // Текущая директория: хост переданной ссылки
        $curentDir = $url["host"];

        // Если текущий хост с начала сторки НЕ равен хосту из конфига возвращаем true - т.е. пропускаем ссылку
        $extLink = (substr($curentDir, 0, strlen($startDir)) != $startDir);

        return $extLink;
    }
}

$A = new ParseIt();
