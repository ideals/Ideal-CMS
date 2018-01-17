<?php
use Cron\CronClass;

// Ищем корневую папку сайта
$_SERVER['DOCUMENT_ROOT'] = $siteFolder = stream_resolve_include_path(__DIR__ . '/../../../..');

$isConsole = true;
require_once $siteFolder . '/_.php';

// Регистрируем автолоадер для библиотеки cron-expression
spl_autoload_register('Cron\CronClass::autoloader', true);

$cron = new CronClass();

// Если запуск тестовый, то выполняем только необходимые тесты
if (isset($argv[1]) && $argv[1] == 'test') {
    $res = $cron->testAction();
} else {
    $res = $cron->runAction();
}

echo $res;
