<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace YandexTurboPage;

class TurboClass
{
    /** @var float Время начала работы скрипта */
    protected $start;

    /** @var \SimpleXMLElement Фид яндекса в виде объекта */
    protected $rss;

    /** @var array Массив для данных из конфига */
    public $config = array();

    /** @var string Переменная содержащая адрес сайта */
    protected $host;

    /** @var bool Флаг необходимости кэширования echo/print */
    public $ob = false;

    /** @var bool Флаг необходимости принудительного сбра фида */
    protected $forceParse = false;

    /** @var bool Флаг необходимости сброса ранее собранных страниц */
    protected $clearTemp = false;

    /** @var bool Допустимое количество элементов в одном фиде */
    protected $itemsLimit = 500;

    /** @var array Массив параметров curl для получения заголовков и html кода страниц */
    protected $options = array(
        CURLOPT_RETURNTRANSFER => true, //  возвращать строку, а не выводить в браузере
        CURLOPT_VERBOSE => false, // вывод дополнительной информации (?)
        CURLOPT_HEADER => true, // включать заголовки в вывод
        CURLOPT_ENCODING => '', // декодировать запрос используя все возможные кодировки
        CURLOPT_AUTOREFERER => true, // автоматическая установка поля referer в запросах, перенаправленных Location
        CURLOPT_CONNECTTIMEOUT => 4, // кол-во секунд ожидания при соединении (мб лучше CURLOPT_CONNECTTIMEOUT_MS)
        CURLOPT_TIMEOUT => 4, // максимальное время выполнения функций cURL функций
        CURLOPT_FOLLOWLOCATION => false, // не идти за редиректами
        CURLOPT_MAXREDIRS => 0, // максимальное число редиректов
        64 => false, // CURLOPT_SSL_VERIFYPEER не проверять ssl-сертификат
        81 => 0, // CURLOPT_SSL_VERIFYHOST не проверять ssl-сертификат
    );

    /**
     * Инициализация счетчика времени работы скрипта,
     * инициализация свойства надобности буферизации вывода,
     * проверка типа запуска (тестовый или по расписанию),
     * проверка надобности сборса ранее собранных данных
     */
    public function __construct()
    {
        // Время начала работы скрипта
        $this->start = microtime(1);
        
        // Проверяем надобность сброса ранее собранных страниц и повторного сбора фида
        $argv = !empty($_SERVER['argv']) ? $_SERVER['argv'] : array();
        if (isset($_GET['w']) || in_array('w', $argv)) {
            $this->forceParse = true;
        }
        if (isset($_GET['с']) || in_array('с', $argv)) {
            $this->clearTemp = true;
        }
    }

    /**
     * Функция отправки сообщений на почту
     *
     * @param string $text Сообщение(отчет)
     * @param string $to Email того, кому отправить письмо
     * @param string $subject Тема письма
     */
    public function sendEmail($text, $to = '', $subject = '')
    {
        $header = "MIME-Version: 1.0\r\n"
            . "Content-type: text/plain; charset=utf-8\r\n"
            . 'From: turbofeed@' . $this->host;

        if (empty($to) && empty($this->config['error_email_notify'])) {
            echo $text;
            return;
        }

        $to = empty($to) ? $this->config['error_email_notify'] : $to;
        $subject = empty($subject) ? $this->host . ' yandex turbo-pages' : $subject;

        // Отправляем письма об изменениях
        mail($to, $subject, $text, $header);
    }

    /**
     * Метод основного цикла для составления фида турбо-страниц
     * @throws \Exception
     */
    public function run()
    {
        $time = microtime(1);

        // Считываем настройки для создания фида
        $this->loadConfig();

        // Проверка существования файла yandexrss и его даты
        $this->prepareYandexRssFile();

        /** @var array Ссылки на страницы, которые нужно представить в турбо-фиде */
        $links = $this->getLinksFromSitemap();

        // Проверка полученных ссылок для обхода
        if (!is_array($links) || empty($links)) {
            $this->stop('Не удалось получить ссылки для обхода из карты сайта.');
        }

        // Подготавливаем RSS-файл, содержащий промежуточные значения между запуском скрипта
        $this->rss = $this->getTempFile();

        while (count($links) > 0) {
            // Если текущее время минус время начала работы скрипта больше 50 секунд - завершаем работу скрипта
            if (($time - $this->start) > 50) {
                break;
            }

            // Делаем паузу между чтением страниц
            usleep(1000000);

            // Устанавливаем указатель на 1-й элемент
            $includeUrl = reset($links);

            // Извлекаем первую ссылку
            $url = key($links);

            $contentToFeed = '';
            // Получаем html-код страницы только для активных страниц
            if ($includeUrl) {
                $content = $this->getContent($url);
                // Парсим нужную часть контента для вставки в фид
                $contentToFeed = $this->getContentToFeed($content);
            }

            // Если нет контента страницы, то не добавляем её в фид
            if (empty($contentToFeed)) {
                print 'Пропускаем страницу ' . $url . "\n";
                unset($links[$url]);
                $time = microtime(1);
                continue;
            }

            // Добавляем полученный контент к фиду
            $item = $this->rss->channel->addChild('item');
            $item->addAttribute('turbo', $includeUrl && $contentToFeed ? 'true' : 'false');
            $item->addChild('link', $url);

            // Добавление контента страницы в CDATA
            $child = $item->addChild('xmlsn:turbo:content');
            $node = dom_import_simplexml($child);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($contentToFeed));

            // И удаляем из массива непройденных
            unset($links[$url]);

            $time = microtime(1);
        }

        if (count($links) > 0) {
            $this->saveParsedUrls($links);
            $this->saveTempFeed();
            $message = "\nВыход по таймауту\n"
                . 'Всего непройденных ссылок: ' . count($links) . "\n"
                . 'Затраченное время: ' . ($time - $this->start) . "\n\n"
                . "Everything it's alright.\n\n";
            $this->stop($message, false);
        }

        $time = microtime(1);

        $this->compare();

        echo "\nФид успешно создан и сохранён в файл {$this->config['yandexRssFile']}\n"
            . 'Количество записей: ' . count($this->rss->channel->item) . "\n"
            . 'Time: ' . ($time - $this->start);
    }

    /**
     * Загрузка конфига в переменную $this->config
     */
    protected function loadConfig()
    {
        // Подгрузка конфига
        $config = __DIR__ . '/site_turbo.php';
        $message = 'Working with settings php-file from local directory';

        // Проверяем наличие файла рядом с запускаемым скриптом
        if (!file_exists($config)) {
            // Проверяем, есть ли конфигурационный файл в корневой папке Ideal CMS
            $config = substr(__DIR__, 0, stripos(__DIR__, '/Ideal/Library/YandexTurboPage')) . '/site_turbo.php';
            $message = 'Working with settings php-file from config directory';
            if (!file_exists($config)) {
                // Конфигурационный файл нигде не нашли :(
                $this->stop("Configuration file {$config} not found!");
            }
        }

        echo $message . "\n";

        /** @noinspection PhpIncludeInspection */
        $this->config = require($config);

        if (empty($this->config['tagLimiter'])) {
            $this->stop('В настройках не указан тег содержащий контент');
        }

        $tmp = parse_url($this->config['website']);
        $this->host = empty($tmp['host']) ? $this->config['website'] : $tmp['host'];

        // Массив значений по умолчанию
        $default = array(
            'yandexRssFile' => '/feed/yandexrss.xml',
            'sitemapFile' => '/sitemap.xml',
            'yandexRssTempFile' => '/feed/yandexTempRss.xml',
            'linksFile' => '/feed/links',
            'disallow_regexp' => '',
            'disable_regexp' => ''
        );
        foreach ($default as $key => $value) {
            if (!isset($this->config[$key])) {
                $this->config[$key] = $value;
            }
        }

        // Строим массивы для пропуска GET-параметров и URL по регулярным выражениям
        $this->config['disallow_regexp'] = explode("\n", $this->config['disallow_regexp']);

        // Строим массивы для деактивации адресов в фиде
        $this->config['disable_regexp'] = explode("\n", $this->config['disable_regexp']);
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
            $this->sendEmail($message, '', $this->host . ' turbo feed error');
        }
        throw new \Exception($message);
    }

    /**
     * Проверяет возможность работы с файлом фида турбо-страниц для яндекса
     *
     * @throws \Exception
     */
    protected function prepareYandexRssFile()
    {
        $rssFile = $this->config['pageroot'] . $this->config['yandexRssFile'];

        // Проверяем существует ли файл и доступен ли он для чтения и записи
        if (file_exists($rssFile)) {
            if (!is_readable($rssFile)) {
                $this->stop("File {$rssFile} is not readable!");
            }
            if (!is_writable($rssFile)) {
                $this->stop("File {$rssFile} is not writable!");
            }
        } else {
            if (file_put_contents($rssFile, '') === false) {
                // Файла нет и создать его не удалось
                $this->stop("Couldn't create file {$this->config['yandexRssFile']}!");
            } else {
                // Удаляем пустой файл, т.к. пустого файла не должно быть
                unlink($rssFile);
                return;
            }
        }

        // Проверяем, обновлялся ли сегодня фид
        if (!$this->forceParse && date('d:m:Y', filemtime($rssFile)) === date('d:m:Y')) {
            $this->stop("Feed {$rssFile} already created today! Everything it's alright.");
        }
    }

    /**
     * Создание временного файла и получение из него информации
     *
     * @throws \Exception
     */
    protected function getTempFile()
    {
        $yandexRssTempFile = $this->config['pageroot'] . $this->config['yandexRssTempFile'];
        // Если временного файла нет, то создаём его
        if ($this->clearTemp || !file_exists($yandexRssTempFile)) {
            // Получаем html-код главной страницы
            $content = $this->getContent($this->config['website']);

            // Получаем title и description для составления заголовка фида
            $meta = $this->getMetaTags($content);

            $rss = $this->getNewSimpleXmlObject($meta['title'], $meta['description']);
            $rss->saveXML($yandexRssTempFile);
            return $rss;
        }
        $yandexRssTempFile = file_get_contents($yandexRssTempFile);
        $rss = new \SimpleXMLElement($yandexRssTempFile);
        return $rss;
    }

    /**
     * Извлекает метатеги "title" и "description"
     */
    protected function getMetaTags($content)
    {
        preg_match('/<title>(.*?)<\/title>/i', $content, $title);
        preg_match('/<meta\s+name=[\'"]description[\'"].*?content=[\'"](.*?)[\'"].*?(\/)?>/i', $content, $description);
        if (!$title || !$description) {
            $this->stop("Не указан title или description");
        }
        return array('title' => $title[1], 'description' => $description[1]);
    }

    /**
     * Получние ссылок из файла карты сайта
     */
    protected function getLinksFromSitemap()
    {
        // Считываем из файла необработанные ссылки
        $links = array();
        if (file_exists($this->config['pageroot'] . $this->config['linksFile'])) {
            // Если возраст файла необработанных ссылок более 23 часов, то считаем что его следует пересобрать
            if (time() - date('U', filemtime($this->config['pageroot'] . $this->config['linksFile'])) > 82800) {
                $this->clearTemp = true;
            }
            if (!$this->clearTemp) {
                $links = file_get_contents($this->config['pageroot'] . $this->config['linksFile']);
            }
        }
        if ($links) {
            $links = json_decode($links, true);
        } else {
            // Если ссылок ещё нет, то парсим ссылки из карты сайта

            // Проверяем существует ли файл
            if (!file_exists($this->config['pageroot'] . $this->config['sitemapFile'])) {
                $this->stop("Нет файла карты сайта из которого берутся адреса для генерации фида");
            }

            $sitemap = file_get_contents($this->config['pageroot'] . $this->config['sitemapFile']);
            $xml = new \SimpleXMLElement($sitemap);
            foreach ($xml->url as $element) {
                $link = (string)$element->loc;

                // Проверяем страницы на надобность деактивации
                foreach ($this->config['disable_regexp'] as $regExp) {
                    if ($regExp && preg_match($regExp, $link)) {
                        $links[$link] = 0;
                        continue 2;
                    }
                }
                foreach ($this->config['disallow_regexp'] as $regExp) {
                    if ($regExp && preg_match($regExp, $link)) {
                        continue 2;
                    }
                }
                $links[$link] = 1;
            }

            // Записываем данные из карты сайта в файл со списком ссылок
            $this->saveParsedUrls($links);
        }

        return $links;
    }

    /**
     * Метод для сохранения распарсенных данных во временный файл
     * @param array $links Список ссылок для сохранения в файл
     */
    protected function saveParsedUrls($links)
    {
        // Записываем данные из карты сайта в файл со списком ссылок
        file_put_contents($this->config['pageroot'] . $this->config['linksFile'], json_encode($links));
    }

    /**
     * Сохранение временных данных в файл фида, удаление всех временных файлов
     */
    protected function compare()
    {
        $this->saveTempFeed();

        // Проверяем количество элементов в получившемся фиде и если превысили лимит, то разбиваем по нескольким файлам
        if (count($this->rss->channel->item) > $this->itemsLimit) {
            // Проверяем не превысит ли количество файлов фидов допустимое значение из настроек
            $countFilesFeed = ceil(count($this->rss->channel->item) / $this->itemsLimit);
            if ($countFilesFeed > $this->config['max_file_feed_num']) {
                $message = 'Количество созданных файлов-фидов (' . $countFilesFeed . ')';
                $message .= ' превышает указанные в настройках значения (' . $this->config['max_file_feed_num'] . ')';
                $this->sendEmail($message, $this->config['manager_email_notify']);
            }
            $filesParts = pathinfo($this->config['yandexRssFile']);
            $i = 1;
            $fileNum = '';
            $newFeedRss = $this->getNewSimpleXmlObject();
            foreach ($this->rss->channel->item as $item) {
                if ($i > $this->itemsLimit) {
                    $pathToSave = $this->config['pageroot'] . $filesParts['dirname'] . $filesParts['filename'];
                    $pathToSave .= $fileNum . '.' . $filesParts['extension'];
                    $newFeedRss->saveXML($pathToSave);
                    $newFeedRss = $this->getNewSimpleXmlObject();
                    $fileNum = (int)$fileNum + 1;
                    $i = 1;
                }
                $newItem = $newFeedRss->channel->addChild('item');
                $newItem->addAttribute('turbo', 'true');
                $newItem->addChild('link', $item->link);
                $newItem->addChild('turbo:turbo:content', $item->children('turbo', true)->content);
                $i++;
            }
            $pathToSave = $this->config['pageroot'] . $filesParts['dirname'] . $filesParts['filename'] . $fileNum;
            $pathToSave .= '.' . $filesParts['extension'];
            $newFeedRss->saveXML($pathToSave);
        } else {
            // Копируем временный фид в актуальный
            $pathToYandexRssFile = $this->config['pageroot'] . $this->config['yandexRssFile'];
            $yandexRssTempFile = file_get_contents($this->config['pageroot'] . $this->config['yandexRssTempFile']);
            file_put_contents($pathToYandexRssFile, $yandexRssTempFile);
        }

        // Удаляем временный файл
        unlink($this->config['pageroot'] . $this->config['yandexRssTempFile']);

        // Очищаем файл ссылок
        file_put_contents($this->config['pageroot'] . $this->config['linksFile'], '');
    }

    /**
     * Сохранение промежуточных значений во временный файл
     */
    protected function saveTempFeed()
    {
        $this->rss->saveXML($this->config['pageroot'] . $this->config['yandexRssTempFile']);
    }

    /**
     * Метод для получения html-кода страницы по адресу $k в основном цикле
     *
     * @param string $url Ссылка на страницу для получения её контента
     * @return string Html-код страницы
     * @throws \Exception
     */
    protected function getContent($url)
    {
        // Инициализируем CURL для получения содержимого страницы
        $ch = curl_init($url);
        curl_setopt_array($ch, $this->options);
        $res = curl_exec($ch); // получаем html код страницы, включая заголовки
        $info = curl_getinfo($ch); // получаем информацию о запрошенной странице

        // Если страница недоступна, то прекращаем выполнение скрипта
        if ($info['http_code'] != 200) {
            $this->stop("Страница {$url} недоступна. Статус: {$info['http_code']}.");
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE); // получаем размер header'а
        curl_close($ch);
        $res = substr($res, $header_size); // вырезаем html код страницы

        // Заменяем в тексте страницы неподходящие символы на знаки вопроса
        $res = htmlspecialchars($res, ENT_NOQUOTES | ENT_DISALLOWED);
        $res = htmlspecialchars_decode($res, ENT_NOQUOTES | ENT_DISALLOWED);

        return $res;
    }

    /**
     * Получает контент для отображения в турбо-страницах
     * @param string $content Контент страницы полностью
     * @return string Контент для турбо страницы
     */
    protected function getContentToFeed($content)
    {
        $turboContent = '';
        // Берём контент сотмеченный для фида
        $contentFindPattern = "/<!--{$this->config['tagLimiter']}-->(.*)<!--end_{$this->config['tagLimiter']}-->/iusU";
        preg_match_all($contentFindPattern, $content, $turboContentParts);
        if ($turboContentParts &&
            isset($turboContentParts[1]) &&
            is_array($turboContentParts[1]) &&
            !empty($turboContentParts[1])
        ) {
            foreach ($turboContentParts[1] as $turboContentPart) {
                $turboContent .= $turboContentPart;
            }
        }

        $allowedTags = array(
            '<img>',
            '<table>',
            '<tr>',
            '<th>',
            '<td>',
            '<h1>',
            '<h2>',
            '<h3>',
            '<h4>',
            '<h5>',
            '<iframe>',
            '<p>',
            '<br>',
            '<ul>',
            '<ol>',
            '<li>',
            '<b>',
            '<strong>',
            '<i>',
            '<sup>',
            '<sub>',
            '<ins>',
            '<del>',
            '<small>',
            '<big>',
            '<pre>',
            '<abbr>',
            '<u>',
            '<a>',
        );

        // Заменяем в тексте страницы неподходящие для XML символы на их кодовое представление
        $turboContent = htmlentities($turboContent, ENT_NOQUOTES | ENT_DISALLOWED, 'UTF-8');
        $turboContent = html_entity_decode($turboContent, ENT_XML1, 'UTF-8');

        $turboContent = strip_tags($turboContent, implode('', $allowedTags));

        // Ищем картинки в тексте для турбостраниц
        preg_match_all('/<img/', $turboContent, $matches);
        if (isset($matches[0]) && $matches[0] && count($matches[0]) > 30) {
            $turboContent = '';
        }

        // Оборачиваем заголовки страниц нужными тегами
        $turboContent = preg_replace('/<h1.*>\s*(.*)<\/h1>/isU', '<header><h1>$1</h1></header>', $turboContent);

        return trim($turboContent);
    }

    /**
     * Генерация скелета нового XML-документа фида в виде объекта "SimpleXMLElement"
     *
     * @param string $title Значение тэга "title" нового XML-документа
     * @param string $description Значение тэга "description" нового XML-документа
     * @return \SimpleXMLElement
     */
    protected function getNewSimpleXmlObject($title = '', $description = '')
    {
        $rss = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><rss></rss>');
        $rss->addAttribute('xmlns:xmlns:atom', 'http://www.w3.org/2005/Atom');
        $rss->addAttribute('xmlns:xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $rss->addAttribute('xmlns:xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $rss->addAttribute('xmlns:xmlns:turbo', 'http://turbo.yandex.ru');
        $rss->addAttribute('version', '2.0');
        $channel = $rss->addChild('channel');
        $channel->addChild('title', $title ? $title : $this->rss->channel->title);
        $channel->addChild('link', $this->config['website']);
        $channel->addChild('description', $description ? $description : $this->rss->channel->description);
        return $rss;
    }
}
