<?php
// Подключаем Composer
require_once __DIR__ .  '/../../../../../vendor/autoload.php';
require_once __DIR__ . '/CronClass.php';

$cron = new \Cron\CronClass();

// Если запуск тестовый, то выполняем только необходимые тесты
if (isset($argv[1]) && $argv[1] === 'test') {
    $cron->testAction();
    echo $cron->getMessage();
} else {
    $cron->runAction();
}
