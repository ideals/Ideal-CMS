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

    private $excess = array(
        '/users',
        '/account'
    );

    public function __construct()
    {
        // Время начала работы скрипта
        $this->start = microtime(1);

        $this->loadConfig();

        // Проверка существования файла sitemap.xml и его даты
        if ($this->isSiteMapExist()) {
            echo 'Карта сайта уже создана';
            exit;
        }
        //list($this->links, $this->checked) = $this->loadParsedUrls();
        $this->loadParsedUrls();

        if ((count($this->links) == 0) && (count($this->checked) == 0)) {
            // Если это самое начало сканирования, добавляем в массив для сканирования первую ссылку
            $this->links[$this->config['website']] = 0;
        }

        $this->run();
    }

    protected function info($mes, $status)
    {
        // $status: 0 - продолжить работу, 1 - остановить скрипт
        echo $mes . "</br>";
        if ($status == 1) {
            exit();
        }
    }

    protected function loadConfig()
    {
        // Подгрузка конфига
        $config = __DIR__ . "/sitemap.php";

        if (!file_exists($config)) {
            exit("Конфигурационный файл {$config} не найден!");
        } else {
            $this->config = require($config);
            rtrim($this->config['website'], '/');
            $tmp = parse_url($this->config['website']);
            $this->host = $tmp['host'];
        }
    }

    protected function isSiteMapExist()
    {
        $xmlFile = $this->config['pageroot'] . $this->config['sitemap_file'];

        // Проверяем существует ли файл и доступен ли он для чтения и записи
        if (file_exists($xmlFile)) {
            if (!is_readable($xmlFile)) {
                exit("Карта сайта {$xmlFile} не доступна для чтения!");
            }
            if (!is_writable($xmlFile)) {
                exit("Карта сайта {$xmlFile} не доступна для записи!");
                return false;
            }
            // Проверяем, обновлялась ли сегодня карта сайта
            if (date('d:m:Y', filemtime($xmlFile)) == date('d:m:Y')) {
                exit("Карта сайта {$xmlFile} уже создавалась сегодня!");
            }
            return true;
        } else {
            // Файла нет, пытаемся создать
            if (file_put_contents($xmlFile, '') === false) {
                // Создать файл не получилось
                exit("Не удалось создать файл {$xmlFile} для карты сайта!");
            }
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
            // todo переделать условие на нормальное, зависящее от времени выполнения скрипта и времени на сохранение данных
            if (($time - $this->start) > 58.00) {
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

    /* Метод преобразования специальных символов для xml файла карты сайта */
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

    protected function saveSiteMap()
    {

        $ret = array();

        $ret[] = sprintf('<?xml version="1.0" encoding="%s"?>', 'UTF-8');
        $ret[] = sprintf(
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'
        );
        $ret[] = sprintf(
            '<!-- Last update of sitemap %s -->',
            date('Y-m-d\TH:i:s') . substr(date("O"), 0, 3) . ":" . substr(date("O"), 3)
        );

        foreach ($this->checked as $k => $v) {
            $ret[] = '<url>';
            $ret[] = sprintf('<loc>%s</loc>', $this->xmlEscape($k));
            // Временно без частоты обновления, даты последнего изменения и приоритета
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
            if (isset($url['changefreq'])) {
                $ret[] = sprintf(
                    '<changefreq>%s</changefreq>',
                    $this->xmlEscape($url['changefreq'])
                );
            }
            */
            if (isset($this->config['priority'])) {
                $priorityStr = sprintf('<priority>%s</priority>', '%01.1f');
                $ret[] = sprintf($priorityStr, $this->config['priority']);
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
        echo 'done' . ($time - $this->start);
    }

    /* Метод для получения html кода и парсинга текущей страницы в основном цикле */
    private function getUrl($k, $place)
    {
        $ch = curl_init($k);

        curl_setopt_array($ch, $this->options);

        $res = curl_exec($ch); //получаем html код страницы, включая заголовки

        $info = curl_getinfo($ch); // получаем инофрмацию о запрошенной странице

        // Если страница недоступна прекращаем выполнение скрипта
        if ($info['http_code'] >= '400' && $info < '599') {
            exit('Страница' . $k . 'недоступна. Ошибка' . $info['http_code'] . ". Переход с $place");
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE); // получаем размер header'а

        curl_close($ch);

        $res = substr($res, $header_size); //вырезаем html код страницы

        return $res;
    }


    protected function parseLinks($content)
    {
        // Получение всех ссылок со страницы
        preg_match_all(self::LINK, $content, $urls);

        return $urls[1];
    }

    /* Метод для обработки полученных ссылок в результате парсинга страницы */
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
                if ($url['host'] != $this->host && ((("www." . $url['host']) == $this->host) && $this->withWWW == true || ($url['host'] == ("www." . $this->host)) && $this->withWWW == false)) {
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
        if ($url['host'] != $this->host && (strpos($url['host'], $this->host) != false || strpos($this->host, $url['host']) != false)) {
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

    protected function isExternalLink($link)
    {
        // Если ссылка на приложение - пропускаем её
        if (preg_match(',^(ftp://|mailto:|news:|javascript:|telnet:|callto:|skype:),i', $link)) {
            return true;
        }

        $url = parse_url($link);

        $tmp = $this->config['website'];
        $tmp = parse_url($tmp);
        $this->host = $tmp['host'];
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
