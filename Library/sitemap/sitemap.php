<?php
return array(
    'pageroot' => '/var/www/test/public_html', // Корневая папка сайта на диске (только если скрипт без этого не работает) | Ideal_Text
    'website' => 'http://www.neox.ru/', // Сайт для сканирования | Ideal_Text
    'sitemap_file' => '/sitemap.xml', // Файл для записи xml-карты сайта | Ideal_Text
    'tmp_file' => '/tmp/sitemap.part', // Путь от корня сайта к временному файлу | Ideal_Text
    'priority' => '0.8', // Приоритет для всех страниц | Ideal_Text
    'change_freq' => 'weekly', // Частота обновления страниц
    'seo_urls' => 'http://www.neox.ru/ = 1', // Приоритет для продвигаемых ссылок
    'disallow_key' => 'sid\nPHPSESSID', // GET параметры, отбрасываемые при составлении карты сайта
    'script_time' => '60', // Максимальное время работы скрипта
    'recording' => '0.5' // Минимальное время на запись в промежуточный файл
);
