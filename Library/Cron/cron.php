<?php
require __DIR__ . '/loader.php';

$cron = new \Cron\CronClass();

// Если запуск тестовый, то выполняем только необходимые тесты
if (isset($argv[1]) && $argv[1] === 'test') {
    $cron->testAction();
    echo $cron->getMessage();
} else {
    $cron->runAction();
}
