<?php
namespace ParseIt;

use Ideal\Field\Url;

class ParseIt
{
    /** Регулярное выражение для поиска ссылок */
    const LINK = "/<[Aa][^>]*[Hh][Rr][Ee][Ff]=['\"]?([^\"'>]+)[^>]*>/";

    /** @var float Время начала работы скрипта */
    private $start;

    /** @var string Переменная содержащая адрес главной страницы сайта */
    private $host;

    /** @var  array Массив НЕпроверенных ссылок */
    private $links = array();

    /** @var array Массив проверенных ссылок */
    private $checked = array();

    /** @var array Массив для данных из конфига */
    private $config = array();

    /** @var string Ссылка из мета-тега base, если он есть на странице */
    private $base;

    /** @var array Массив параметров curl для получения заголовков и html кода страниц */
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

    /**
     * Инициализация счетчика времени работы скрипта, вызов метода загрузки конфига,
     * определение хоста, вызов методов проверки существования карты сайта и загрузки временных
     * данных (при их наличии), запуск метода основного цикла скрипта.
     */
    public function __construct()
    {
        // Время начала работы скрипта
        $this->start = microtime(1);

        // Считываем настройки для создания карты сайта
        $this->loadConfig();

        // Проверка существования файла sitemap.xml и его даты
        $this->prepareSiteMapFile();

        // Загружаем данные, собранные на предыдущих шагах работы скрипта
        $this->loadParsedUrls();

        // Задаем время на запись временного файла
        $this->setTimeout();

        if ((count($this->links) == 0) && (count($this->checked) == 0)) {
            // Если это самое начало сканирования, добавляем в массив для сканирования первую ссылку
            $this->links[$this->config['website']] = 0;
        }

        $this->run();
    }

    /**
     * Вывод сообщения и завершение работы скрипта
     *
     * @param string $message - сообщение для вывода
     */
    protected function stop($message)
    {
        echo $message;
        exit();
    }

    /**
     * Установка времени, необходимого для записи данных в временных файл
     */
    protected function setTimeout()
    {
        $count = count($this->links) + count($this->checked);
        if ($count > 1000) {
            $this->config['recording'] = ($count/1000) * 0.05  + $this->config['recording'];
        }
    }

    /**
     * Загрузка конфига в переменную $this->config
     */
    protected function loadConfig()
    {
        // Подгрузка конфига
        $config = __DIR__ . '/sitemap.php';

        if (!file_exists($config)) {
            $this->stop("Конфигурационный файл {$config} не найден!");
        } else {
            /** @noinspection PhpIncludeInspection */
            $this->config = require($config);

            $tmp = parse_url($this->config['website']);
            $this->host = $tmp['host'];

            // Если существует строка с ненужными GET параметрами - разбиваем её на массив
            if (!empty($this->config['disallow_key'])) {
                $this->config['disallow_key'] = explode("\n", $this->config['disallow_key']);
            }

            // Если заданы страницы с приоритетом, парсим их в массив
            if (!empty($this->config['seo_urls'])) {
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

    /**
     * Проверка наличия, доступности для записи и актуальности xml-файла карты сайта
     */
    protected function prepareSiteMapFile()
    {
        $xmlFile = $this->config['pageroot'] . $this->config['sitemap_file'];

        // Проверяем существует ли файл и доступен ли он для чтения и записи
        if (file_exists($xmlFile)) {
            if (!is_readable($xmlFile)) {
                $this->stop("Карта сайта {$xmlFile} недоступна для чтения!");
            }
            if (!is_writable($xmlFile)) {
                $this->stop("Карта сайта {$xmlFile} недоступна для записи!");
            }
            // Проверяем, обновлялась ли сегодня карта сайта
            if (date('d:m:Y', filemtime($xmlFile)) == date('d:m:Y')) {
                $this->stop("Карта сайта {$xmlFile} уже создавалась сегодня!");
            }
        } elseif ((file_put_contents($xmlFile, '') === false)) {
            // Файла нет и создать его не удалось
            $this->stop("Не удалось создать файл {$xmlFile} для карты сайта!");
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
        }
    }

    /**
     * Метод для сохранения распарсенных данных во временный файл
     */
    protected function saveParsedUrls()
    {
        $result = array(
            $this->links,
            $this->checked
        );

        $result = serialize($result);

        $tmp_file = __DIR__ . $this->config['tmp_file'];

        if (file_exists($tmp_file)) {
            if (!is_writable($tmp_file)) {
                $this->stop("Временный файл {$tmp_file} недоступен для записи!");
            }
        } elseif ((file_put_contents($tmp_file, '') === false)) {
            // Файла нет и создать его не удалось
            $this->stop("Не удалось создать временный файл {$tmp_file} для карты сайта!");
        }

        $fp = fopen($tmp_file, 'w');

        fwrite($fp, $result);

        fclose($fp);
    }

    /**
     * Метод основного цикла для сборка карты сайта и парсинга товаров
     */
    private function run()
    {
        /** Массив links вида [ссылка] => пометка(не играет роли) */
        /** Массив checked вида [ссылка] => пометка о том является ли ссылка корректной (1 - да, 0 - нет) */
        $time = microtime(1);
        while (count($this->links) > 0) {
            $time = microtime(1);
            // Если текущее время минус время начала работы скрипта больше чем разница
            // заданного времени работы скрипта и времени на запись в файл - завершаем работу скрипта
            if (($time - $this->start) > ($this->config['script_time'] - $this->config['recording'])) {
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
            $trans[chr(38)] = '&amp;';
        }
        return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};)/", "&#38;", strtr($str, $trans));
    }

    /**
     * Метод создания xml файла с картой сайта
     */
    protected function saveSiteMap()
    {
        $lastDate = date('Y-m-d\TH:i:s') . substr(date("O"), 0, 3) . ":" . substr(date("O"), 3);

        $ret = '';
        foreach ($this->checked as $k => $v) {
            $ret .= '<url>';
            // todo а разве вместо xmlEscape не подойдёт url_encode???
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

        $time = microtime(1);
        echo 'done ' . ($time - $this->start);
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
        if ($info['http_code'] >= '400' && $info < '599') {
            $this->stop("Страница {$k} недоступна. Ошибка {$info['http_code']}. Переход с {$place}");
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
            // Убираем все флаги без ссылок
            if (strpos($url, '#') === 0) {
                continue;
            }

            if ($this->isExternalLink($url, $current)) {
                // Пропускаем ссылки на другие сайты
                continue;
            }
            // Абсолютизируем ссылку
            $link = $this->getAbsoluteUrl($url, $current);

            // Убираем лишние GET параметры из ссылки
            $link = $this->cutExcessGet($link);

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
     * @param string $link  Обрабатываемая ссылка
     * @param string $current  Текущая страница с которой получена ссылка
     * @return string  Возвращается абсолютная ссылка
     */
    protected function getAbsoluteUrl($link, $current)
    {
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
     *
     * @param string $link Проверяемая ссылка
     * @param string $current Текущая страница с которой получена ссылка
     * @return boolean true если ссылка внешняя, иначе false
     */
    protected function isExternalLink($link, $current)
    {
        // Если ссылка на приложение - пропускаем её
        if (preg_match(',^(ftp://|mailto:|news:|javascript:|telnet:|callto:|skype:),i', $link)) {
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
     * @param $url Обрабатываемая ссылка
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

                // Разбиваем параметры
                parse_str($link['query'], $parts);

                foreach ($parts as $k => $v) {
                    // Если параметр есть в исключениях - удаляем его из массива
                    if ($v == $key) {
                        unset($parts[$k]);
                    }
                }
                // Собираем оставшиеся параметры в строку
                $query = http_build_query($parts);
                // Заменяем GET параметры оставшимися
                $link['query'] = $query;

                $urlModel = new Url\Model();

                $url = $urlModel->unparseUrl($link);

            }
        }
        // Если в сслыке есть '#' якорь, то обрезаем его
        if (strpos($url, '#') !== false) {
            $url = substr($url, 0, strpos($url, '#'));
        }
        // Если последний символ в ссылке '&' - обрезаем его
        while (substr($url, strlen($url) - 1) == "&") {
            $url = substr($url, 0, strlen($url) - 1);
        }
        // Если последний символ в ссылке '?' - обрезаем его
        while (substr($url, strlen($url) - 1) == "?") {
            $url = substr($url, 0, strlen($url) - 1);
        }
        return $url;
    }
}
