<?php
// @codingStandardsIgnoreFile
return array(
    'pageroot' => "", // Корневая папка сайта на диске (только если скрипт без этого не работает) | Ideal_Text
    'website' => "http://example.com", // Сайт для сканирования | Ideal_Text
    'sitemap_file' => "/sitemap.xml", // Файл для записи xml-карты сайта | Ideal_Text
    'script_timeout' => "60", // Максимальное время выполнения скрипта (секунды) | Ideal_Text
    'load_timeout' => "10", // Максимальное время ожидания получения одного URL (секунды) | Ideal_Text
    'recording' => "0.5", // Минимальное время на запись в промежуточный файл (секунды) | Ideal_Text
    'existence_time_file' => "25", // Максимальное время существования версии промежуточного файла (часы) | Ideal_Text
    'delay' => "1", // Задержка между запросами URL (секунды) | Ideal_Text
    'tmp_file' => "/tmp/sitemap.part", // Путь от корня сайта к временному файлу | Ideal_Text
    'old_sitemap' => "/tmp/sitemap-old.part", // Путь от корня сайта к файлу предыдущего сканирования | Ideal_Text
    'tmp_radar_file' => "/tmp/radar.part", // Путь от корня сайта к временному файлу отчёта о перелинковке | Ideal_Text
    'old_radar_file' => "/tmp/radar-old.part", // Путь от корня сайта к файлу предыдущего отчёта о перелинковке | Ideal_Text
    'change_freq' => "weekly", // Частота обновления страниц | Ideal_Select | {"dynamic":"dynamic","hourly":"hourly","daily":"daily","weekly":"weekly","monthly":"monthly","yearly":"yearly","never":"never"}
    'priority' => "0.8", // Приоритет для всех страниц | Ideal_Text
    'time_format' => "long", // Формат отображения времени | Ideal_Select | {"long":"long","short":"short"}
    'disallow_regexp' => "/\.(xml|inc|txt|js|zip|bmp|jpg|jpeg|png|gif|css)$/i", // Регулярные выражения для файлов, которые не надо включать в карту сайта | Ideal_Area
    'disallow_key' => "sid\nPHPSESSID", // GET параметры, отбрасываемые при составлении карты сайта | Ideal_Area
    'seo_urls' => "http://example.com/promoted-page.html = 0.9", // Приоритет для продвигаемых ссылок | Ideal_Area
    'email_cron' => "help@neox.ru", // Электронная почта для cron-сообщений | Ideal_Text
    'email_notify' => "top@neox.ru", // Электронная почта для уведомления о добавленных/удалённых ссылках | Ideal_Text
    'email_json' => "help@neox.ru", // Электронная почта для уведомлений об изменениях в карте сайта (json-формат) | Ideal_Text
);
