<?php
// @codingStandardsIgnoreFile

// Запрещаем вызов скрипта из браузера
if (isset($_SERVER['REQUEST_URI'])) {
    die('Недопустимое действие');
}

$documentRoot = array_filter(array_slice(explode('/', __DIR__), 0, -1));

// Устанавливаем путь до папки CMS
$pathToCmsFolder = '/' . implode('/', $documentRoot);
define('SETUP_DIR', $pathToCmsFolder);
$coreFolder = array_pop($documentRoot);

// Получаем настройки из файла config.php
$settingsPath = '/' . implode('/', $documentRoot);
$settings = require($settingsPath . '/config.php');

$cmsFolder = trim(array_pop($documentRoot));


// Устанавливаем константу DOCUMENT_ROOT
$documentRoot = '/' . implode('/', $documentRoot);
define('DOCUMENT_ROOT', $documentRoot);

set_include_path(
    get_include_path()
    . PATH_SEPARATOR . DOCUMENT_ROOT . '/' . $cmsFolder . '/' . $coreFolder . '/'
);

// Подключаем автозагрузчик классов,
// для возможности в скриптах обновления использовать все доступые классы
require_once $pathToCmsFolder . '/Core/AutoLoader.php';

// Устанавливаем нужные параметры конфигурации
$config = \Ideal\Core\Config::getInstance();
$config->cmsFolder = $cmsFolder;
$config->db = $settings['db'];
$config->cache = array('memcache' => false);

$versions = new \Ideal\Structure\Service\UpdateCms\Versions();

// Получаем текущую версию из файла логов и файла README.md
$nowVersions = $versions->getVersions();
$nowVersionsFromReadme = $versions->getVersionFromReadme(array(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . $coreFolder));

// Инициализируем модель обновления
$updateCmsModel = new \Ideal\Structure\Service\UpdateCms\Model();

// Устанавливаем признак тестовго режима
$updateCmsModel->setTestMode(true);

// Устанавливаем данные обновления ядра
$updateCmsModel->setUpdate('Ideal-CMS', $nowVersionsFromReadme, $nowVersions);

// Получаем список скриптов для обновления
$scripts = $updateCmsModel->getUpdateScripts();

// Запускаем скрипты обновления
$updateCmsModel->runOldScript($scripts);

// Получаем ответы от запуска скриптов
$answers = $updateCmsModel->getAnswer();

// Выводим сообщения
foreach ($answers['message'] as $value) {
    echo "$value[0]\n";
}