<?php
// @codingStandardsIgnoreFile
return array(
    'pageroot' => "", // Корневая папка сайта на диске | Ideal_Text
    'website' => "", // Сайт для сканирования | Ideal_Text
    'yandexRssFile' => "/yandexrss.xml", // Путь до файла турбо-фида | Ideal_Text
    'sitemapFile' => "/sitemap.xml", // Путь до файла карты сайта | Ideal_Text
    'yandexRssTempFile' => "/tmp/yandexTempRss.xml", // Путь до временного файла турбо-фида | Ideal_Text
    'linksFile' => "/tmp/links", // Путь до файла со списком ссылок из карты сайта | Ideal_Text
    'tagLimiter' => "turbofeed", // тэг html-комментария (например "turbofeed", тогда контент будет браться между тегами &lt;!--turbofeed--&gt;&lt;!--end_turbofeed--&gt;) | Ideal_Text
    'disallow_regexp' => "", // Регулярные выражения для адресов, которые не надо включать в турбо-фид | Ideal_RegexpList
    'disable_regexp' => "", // Регулярные выражения для адресов, которые необходимо деактивировать | Ideal_RegexpList
    'error_email_notify' => "errors@neox.ru", // Электронная почта для уведомления об ошибках в процессе работы скрипта | Ideal_Text
    'manager_email_notify' => "top@neox.ru", // Электронная почта для уведомления менеджера | Ideal_Text
    'max_file_feed_num' => "1", // Максимальное количество файлов-фидов | Ideal_Integer
);
