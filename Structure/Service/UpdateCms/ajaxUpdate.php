<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

/**
 * Обновление IdealCMS или одного модуля
 *
 */

ini_set('display_errors', 'Off');

$cmsFolder = $_POST['config'];
$subFolder = '';

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш.
define('DOCUMENT_ROOT', getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT']);

// В пути поиска по умолчанию включаем корень сайта, путь к Ideal и папке кастомизации CMS
set_include_path(
    get_include_path()
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Ideal.c/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Ideal/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Mods.c/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Mods/'
);

// Подключаем автозагрузчик классов
require_once 'Core/AutoLoader.php';

// Подключаем класс конфига
$config = Ideal\Core\Config::getInstance();

// Каталог, в котором находятся модифицированные скрипты CMS
$config->cmsFolder = $subFolder . $cmsFolder;

// Загружаем список структур из конфигурационных файлов структур
$config->loadSettings();

$updateModel = new \Ideal\Structure\Service\UpdateCms\Model();

// Сервер обновлений
$srv = 'http://idealcms/update';
$getFileScript = $srv . '/get.php';

// Файл лога обновлений
$log = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . 'update.log';

if (!file_exists($log)) {
    $updateModel->uExit('Файл лога обновлений не существует ' . $log);
}

if (file_put_contents($log, '', FILE_APPEND) === false) {
    $updateModel->uExit('Файл ' . $log . ' недоступен для записи');
}

if (is_null($config->cms['tmpFolder']) || ($config->cms['tmpFolder'] == '')) {
    $updateModel->uExit('В настройках не указана папка для хранения временных файлов');
}

// Папка для хранения загруженных файлов обновлений
$uploadDir = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/update';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        $updateModel->uExit('Не удалось создать папку' . $uploadDir);
    }
}

// Папка для разархивации файлов новой CMS
// Пример /www/example.com/tmp/setup/Update
define('SETUP_DIR', $uploadDir . '/setup');
if (!file_exists(SETUP_DIR)) {
    if (!mkdir(SETUP_DIR, 0755, true)) {
        $updateModel->uExit('Не удалось создать папку' . SETUP_DIR);
    }
}

$updateModel->setUpdateFolders(
    array(
        'getFileScript' => $getFileScript,
        'uploadDir' => $uploadDir
    )
);

// Загрузка файла

// todo Сделать защиту от хакеров на POST-переменные

if (!isset($_POST['version']) || !isset($_POST['name'])) {
    $updateModel->uExit('Непонятно, что обновлять. Не указаны version и name');
}

// Скачиваем и распаковываем архив с обновлениями
$updateModel->downloadUpdate($_POST['name'], $_POST['version']);

// Запускаем выполнение скриптов и запросов
$updateModel->updateScripts($_POST['name'], $_POST['version']);

// Модуль установился успешно, делаем запись в лог обновлений
$updateModel->writeLog('Installed ' . $_POST['name'] . ' v. ' . $_POST['version']);

// Удаляем старую папку
$updateModel->removeDirectory($updateCore . '_old');

$updateModel->uExit('Обновление завершено успешно');
