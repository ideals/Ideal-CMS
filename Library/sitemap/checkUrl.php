<?php
namespace ParseIt;

/**
 * Created by PhpStorm.
 * User: Sam
 * Date: 14.02.2015
 * Time: 15:15
 */
class ParseIt
{
    /** Регулярное выражение для проверки url */
    const URL = "/^[a-zA-Z0-9\-\.\:\/_]{3,}$/"; // обращение self::

    /** Регулярное выражение для поиска ссылок */
    const LINK = "/<[Aa][^>]*[Hh][Rr][Ee][Ff]=['\"]?([^\"'>]+)[^>]*>/";

    /** Регулярное выражение для поиска комментариев */
    const COMMENT = "/<\!--.*-->/imsU";

    /** @var  float Время начала работы скрипта */
    private $start;

    /** @var string Переменная содержащая адрес главной страницы сайты */
    private $host;

    /** @var string Переменная для достраивания ссылки до абсолютной */
    private $begin;

    /** @var string Имя хоста без www */
    private $nameUrl;

    /** @var  array Массив с частями переданной ссылки */
    private $url = array();

    /** @var  array Массив НЕпроверенных ссылок */
    private $links = array();

    /** @var array Массив проверенных ссылок */
    private $checked = array();

    /** @var resource Поток для времнного файла  */
    private $tmp;

    private $options = array(
        CURLOPT_RETURNTRANSFER => true, //  возвращать строку, а не выводить в браузере
        CURLOPT_VERBOSE => true, // вывод дополнительной информации (?)
        CURLOPT_HEADER => true, // включать заголовки в вывод
        CURLOPT_FOLLOWLOCATION => true, // следовать по любому заголовку Location
        CURLOPT_ENCODING => "", // декодировать запрос используя все возможные кодировки
        CURLOPT_AUTOREFERER => true, // автоматическая установка поля referer в запросах, перенаправленных Location
        CURLOPT_CONNECTTIMEOUT => 1, // кол-во секунд ожидания при соединении (мб лучше CURLOPT_CONNECTTIMEOUT_MS)
        CURLOPT_TIMEOUT => 1, // максимальное время выполнения функций cURL функций
        CURLOPT_MAXREDIRS => 2, // максимальное число редиректов
    );

    public function __construct()
    {
        //Подгрузка конфига

        //время начала работы скрипта
        $this->start = microtime(1);

        $this->prepareUrl($_POST['site']);
    }

    /** Метод для обработки полученной ссылки */
    private function prepareUrl($url)
    {
        // Проверка регулярным выражением
        if (!preg_match(self::URL, $url)) {
            echo "Некорректный url\n";
            exit();
        }
        // Разбиение на части
        $this->url = parse_url($url);

        $this->checkSiteMap();
    }

    /** Метод для проверки существования карты сайта */
    private function checkSiteMap()
    {
        //для названия файла c картой сайта вырезаем 'www.' из хоста
        if (strripos($this->url['host'], "www.") !== false) {
            $this->nameUrl = str_replace("www.", "", $this->url['host']);
        } else {
            $this->nameUrl = $this->url['host'];
        }
        // Для обхода ссылок лучше всегда обрезать "www."
        // Так проще обрабатывать полученные сслыки
        $this->url['host'] = $this->nameUrl;

        $filename ="C:\\www\\parse-it\\sitemaps\\".$this->nameUrl."_".date("j-n-Y.")."txt";

        //проверяем существование карты сайта сегодняшнего дня
        if (file_exists($filename)) {
            echo "Карта сайта уже есть\n";
            exit();
        }

        $this->accessCheck();

    }

    /** Метод для проверки доступнности введенного сайта */
    private function accessCheck()
    {
        //создаем абсолютную ссылку с хостом
        $this->host = 'http://'.$this->url['host'].'/';

        //переменная для достраивания ссылок, начинающихся со '/'
        $this->begin = "http://".$this->url['host'];

        $headers = get_headers($this->host); // получаем заголовки сайта

        //если сайт не доступен, выводим соответствующее сообщение
        if (!in_array("HTTP/1.1 200 OK", $headers) and !in_array("HTTP/1.0 200 OK", $headers)) {
            echo "Введенный сайт не доступен\n";
            exit();
        }
        $this->checkTempFile();
    }

    /** Метод для проверки существования временного файла */
    private function checkTempFile()
    {
        //проверяем существование временного файла
        $filename = "C:\\www\\parse-it\\tmp\\".$this->nameUrl."_tmp.txt";
        //Если файл существует считываем его
        if (file_exists($filename)) {
            $arr = file_get_contents($filename);
            $this->links = unserialize($arr);
            //Разбиваем массив на пройденные и непройденные ссылки
            foreach ($this->links as $v => $k) {
                if ($k == 1) {
                    $this->checked[$v] = $k;
                    unset($this->links[$v]);
                }
            }
            $this->tmp = fopen($filename, "w");
        } else {
            // иначе создаем его
            $this->tmp = fopen($filename, "w+");
            // записываем первую сслыку в массив
            $this->links[$this->host] = 0;
            // Если переданная ссылка не главная страница, тоже добавляем её
            if (isset($this->url['path']) and $this->url['path'] != '/') {
                //Добавляем в массив ссылку, достроенную до абсолютной
                $this->links[$this->begin.$this->url['path']] = 0;
            }
        }
        $this->run();
    }

    /** Метод основного цикла для сборка карты сайта и парсинга товаров */
    private function run()
    {
        /** Массив links вида [ссылка] => пометка(не играет роли) */
        /** Массив checked вида [ссылка] => пометка о том является ли ссылка корректной (1 - да, 0 - нет(404)) */
        while (count($this->links) > 0) {
            $time = microtime(1);
            /** Проверка не прошло ли 60 секунд
             *  и есть ли еще время на одну итерацию(2 секунды) */
            if (($time - $this->start) < 60.00 && (60.00 - ($time - $this->start)) > 1.00) {
                // Устанавливаем указатель на 1-й элемент
                reset($this->links);

                // Извлекаем ключ текущего элемента (то есть ссылку)
                $k = key($this->links);

                //Передаем ссылку на парсинг
                $this->getUrl($k);
            } else {
                $result = array_merge($this->links, $this->checked);
                $result = serialize($result);
                fwrite($this->tmp, $result);
                fclose($this->tmp);
                echo "timeout";
                exit();
            }
        }
        echo "Карта сайта успешно создана!\n";
        fclose($this->tmp);
    }

    /* Метод для получения html кода и парсинга текущей страницы в основном цикле */
    private function getUrl($k)
    {
        $ch = curl_init($k);

        curl_setopt_array($ch, $this->options);

        $res = curl_exec($ch); //получаем html код страницы, включая заголовки

        $info = curl_getinfo($ch); // получаем инофрмацию о запрошенной странице

        if ($info['http_code'] == "404") {
            //Если страница имеет статус 404 добавляем её в массив пройденных с соответствующим статусом
            $this->checked[$k] = 1;
            unset ($this->links[$k]);
            return 0;
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE); // получаем размер header'а

        curl_close($ch);

        $res = substr($res, $header_size); //вырезаем html код страницы

        $res = preg_replace(self::COMMENT, '', $res); // удаление комментариев из страницы

        //получение всех ссылок со страницы
        preg_match_all(self::LINK, $res, $urls);

        $this->processingLinks($k, $urls[1]);
    }

    /* Метод для обработки полученных ссылок в результате парсинга страницы */
    private function processingLinks($k, $urls)
    {
        foreach ($urls as $val) {
            //Переменная для флага, является ли данный url - ссылкой на наш сайт
            $right_url = false;

            //Вырезаем "www."
            $val = str_replace("www.", "", $val);

            //Если ссылка начинается со "/" то достраиваем и отмечаем её
            if (strpos($val, '/') === 0) {
                $val = 'http://'.$this->url['host'].$val;
                $right_url = true;
            }
            //Если существует абсолютная ссылка на данный сайт то отмечаем её
            if (strpos($val, "http://".$this->url['host']) === 0) {
                $right_url = true;
            }
            //Если такой ссылки массиве еще нет и ссылка ведет на наш сайт, то добавляем её
            if (!isset($this->links[$val]) and !isset($this->checked[$val]) and $right_url === true) {
                $this->links[$val] = 0;
            }
        }
        // Добавляем текущую ссылку в массив пройденных ссылок
        $this->checked[$k] = 1;
        // И удаляем из массива непройденных
        unset ($this->links[$k]);
    }
}
$A = new ParseIt();
