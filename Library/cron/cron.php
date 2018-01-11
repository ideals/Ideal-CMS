<?php
// Вычисляем путь до файла "site_data.php" в корне админки и абсолютный путь до корня сайта
$siteDataFilePathParts = explode('/', __DIR__);
$siteDataFilePathParts = array_filter($siteDataFilePathParts);
$siteDataFilePathParts = array_slice($siteDataFilePathParts, 0, count($siteDataFilePathParts) - 3);
$siteDataFilePath = '/' . implode('/', $siteDataFilePathParts) . '/site_data.php';
$siteRootPathParts = array_slice($siteDataFilePathParts, 0, count($siteDataFilePathParts) - 1);
$siteRootPath = '/' . implode('/', $siteRootPathParts) . '/';


// Переопределяем стандартный обработчик ошибок для отправки уведомлений на почту
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($siteDataFilePath) {
    // Получаем данные настроек системы
    $siteData = require $siteDataFilePath;

    // Формируем текст ошибки
    $_err = 'Ошибка [' . $errno . '] ' . $errstr . ', в строке ' . $errline . ' файла ' . $errfile;

    // Выводим текст ошибки
    echo $_err . "\n";

    // Формируем заголовки письма и отправляем уведомление об ошибке на почту ответственного лица
    $header ="From: \"{$siteData['domain']}\" <{$siteData['robotEmail']}>\r\n";
    $header.="Content-type: text/plain; charset=\"utf-8\"";
    mail($siteData['cms']['adminEmail'], 'Ошибка при выполнении крона', $_err, $header);
    exit;
});

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

/**
 *  Получает список задач для крона из настроек Ideal CMS
 * @return array Массив со списком задач для крона из настроек Ideal CMS
 */
$cronTasks = function () use ($siteDataFilePath) {
    // Считываем задачи из настроек
    $siteData = require $siteDataFilePath;
    $cronTaskList = $siteData['cron']['crontab'];
    return explode(PHP_EOL, $cronTaskList);
};

/**
 * Разбирает задачу для крона из настроек Ideal CMS
 * @param string $cronTask Строка в формате "* * * * * /path/to/file.php"
 * @return array Массив, где первым элементом является строка соответствующая интервалу запуска задачи по крону,
 *               а вторым элементом является путь до запускаемого файла
 */
$parseCronTask = function ($cronTask) use ($siteRootPath) {
    // Получаем cron-формат запуска файла в первых пяти элементах массива и путь до файла в последнем элементе
    $taskParts = explode(' ', $cronTask, 6);
    $fileTask = '';
    if (count($taskParts) >= 6) {
        $fileTask = array_pop($taskParts);
    }

    // Если запускаемый скрипт указан относительно корня сайта, то абсолютизируем его
    if ($fileTask && strpos($fileTask, '/') !== 0) {
        $fileTask = $siteRootPath . $fileTask;
    }
    $taskExpression = implode(' ', $taskParts);
    return array($taskExpression, $fileTask);
};

/**
 * Делает проверку на доступность файла настроек, правильность заданий в системе и возможность
 * модификации скрипта обработчика крона
 */
$test = function () use ($siteDataFilePath, $cronTasks, $parseCronTask) {
    // Проверяем доступность файла настроек для чтения
    if (is_readable($siteDataFilePath)) {
        echo "Файл с настройками сайта существует и доступен для чтения\n";
    } else {
        echo "Файла с настройками сайта {$siteDataFilePath} не существует или он недоступен для чтения\n";
    }

    $failure = false;
    $taskIsset = false;
    foreach ($cronTasks() as $cronTask) {
        if ($cronTask) {
            list($taskExpression, $fileTask) = $parseCronTask($cronTask);

            // Проверяем правильность написания выражения для крона и существование файла для выполнения
            if (Cron\CronExpression::isValidExpression($taskExpression) !== true) {
                echo "Неверное выражение \"{$taskExpression}\"\n";
                $failure = true;
            }

            if ($fileTask && !is_readable($fileTask)) {
                echo "Файла \"{$fileTask}\" не существует или он недоступен для чтения\n";
                $failure = true;
            } elseif (!$fileTask) {
                echo "Не задан исполняемый файл для выражения \"{$taskExpression}\"\n";
                $failure = true;
            }
            $taskIsset = true;
        }
    }

    // Если в задачах из настройках Ideal CMS не обнаружено ошибок, уведомляем об этом
    if (!$failure && $taskIsset) {
        echo "В задачах из настроек Ideal CMS ошибок не обнаружено\n";
    } elseif (!$taskIsset) {
        echo "Пока нет ни одного задания для выполнения\n";
    }

    // Проверяем доступность запускаемого файла для изменения его даты
    if (!is_writable(__FILE__)) {
        echo "Не получается изменить дату модификации файла \"" . __FILE__ . "\"\n";
    } else {
        echo "Файл \"" . __FILE__ . "\" позволяет вносить изменения в дату модификации\n";
    }
};

// Если запуск тестовый, то выполняем только необходимые тесты
if (isset($argv[1]) && $argv[1] == 'test') {
    $test();
    exit;
}

// Получаем дату модификации скрипта (она же считается датой последнего запуска)
$modifyTime = new \DateTime();
$modifyTime->setTimestamp(filemtime(__FILE__));

// Обрабатываем задачи для крона из настроек Ideal CMS
$now = new \DateTime();
foreach ($cronTasks() as $cronTask) {
    if ($cronTask) {
        list($taskExpression, $fileTask) = $parseCronTask($cronTask);
        if ($taskExpression && $fileTask) {
            // Получаем дату следующего запуска задачи
            $cron = Cron\CronExpression::factory($taskExpression);
            $nextRunDate = $cron->getNextRunDate($modifyTime);
            $now = new \DateTime();

            // Если дата следующего запуска меньше, либо равна текущей дате, то запускаем скрипт
            if ($nextRunDate <= $now) {
                require_once $fileTask;
            }
        }
    }
}

// Изменяем дату модификации скрипта
touch(__FILE__, $now->getTimestamp());
