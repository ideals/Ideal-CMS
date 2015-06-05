<?php
/**
 * Запускаемый скрипт модуля создания карты сайта
 *
 * Возможны несколько вариантов запуска скрипта
 *
 * 1. Из крона (с буферизацией вывода):
 * /bin/php /var/www/example.com/super/Ideal/Library/sitemap/index.php
 *
 * 2. Из командной строки из папки скрипта (без буферизации вывода):
 * /bin/php index.php
 *
 * 3. Из браузера:
 * http://example.com/super/Ideal/Library/sitemap/index.php
 *
 * 4. Принудительное создание карты сайта, даже если сегодня она и создавалась
 * /bin/php index.php w
 *
 * 5. Принудительное создание карты сайта из браузера, даже если сегодня она и создавалась
 * http://example.com/super/Ideal/Library/sitemap/index.php?w=1
 *
 */
require_once __DIR__ . '/Crawler.php';

$crawler = new SiteMap\Crawler();

if ($crawler->ob) {
    ob_start();
}

echo "<pre>\n";

$message = '';
try {
    $crawler->run();
} catch (Exception $e) {
    $message = $e->getMessage();
}

echo $message;

if ($crawler->ob) {
    // Если было кэширование вывода, получаем вывод и отображаем его
    $text = ob_get_contents();
    ob_end_clean();
    echo $text;

    // Если нужно, отправляем письмо с выводом скрипта
    if ($crawler->status == 'cron' && ($crawler->config['email_cron'] != '')) {
        $crawler->sendEmail($text, $crawler->config['email_cron']);
    }
}
