<?php

require_once __DIR__ . 'checkUrl.php';

$crawler = new ParseIt\ParseIt();

// Проверяем, нужно ли кэшировать вывод для ручной отправки письма с результатами
if ($crawler->manualSend) {
    ob_start();
}

$crawler->run();

if ($crawler->manualSend) {
    $text = ob_get_contents();
    ob_end_clean();
    mail(
        $sitemap->config['email_cron'],
        str_replace('http://', '', $sitemap->config['website']) . ' sitemap',
        $text,
        "From: Sitemap Maker <sitemap@" . str_replace('http://', '', $sitemap->config['website']) . ">\r\n"
    );
    echo $text;
}
