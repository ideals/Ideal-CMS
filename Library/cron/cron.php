<?php
// Подключаем все файлы из библиотеки
require_once __DIR__ . '/../cron-expression/src/Cron/FieldInterface.php';
require_once __DIR__ . '/../cron-expression/src/Cron/AbstractField.php';
require_once __DIR__ . '/../cron-expression/src/Cron/CronExpression.php';
require_once __DIR__ . '/../cron-expression/src/Cron/DayOfMonthField.php';
require_once __DIR__ . '/../cron-expression/src/Cron/DayOfWeekField.php';
require_once __DIR__ . '/../cron-expression/src/Cron/FieldFactory.php';
require_once __DIR__ . '/../cron-expression/src/Cron/HoursField.php';
require_once __DIR__ . '/../cron-expression/src/Cron/MinutesField.php';
require_once __DIR__ . '/../cron-expression/src/Cron/MonthField.php';
require_once __DIR__ . '/../cron-expression/src/Cron/YearField.php';
require_once __DIR__ . '/CronClass.php';

$cron = new Cron\CronClass();

// Если запуск тестовый, то выполняем только необходимые тесты
if (isset($argv[1]) && $argv[1] == 'test') {
    $res = $cron->testAction();
} else {
    $res = $cron->runAction();
}

echo $res;
