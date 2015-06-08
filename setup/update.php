<?php
// @codingStandardsIgnoreFile

// todo Избавиться от констант DOCUMENT_ROOT и SETUP_DIR

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

/**
 * Этот скрипт предназначен для запуска локального обновления на одну версию.
 *
 * Новая версия может быть распакована поверх старой, тогда скрипт запускается
 * без параметров и фактически выполняет только скрипты обновления, которые
 * срабатывают после замены файлов CMS.
 *
 * Чтобы проверить работу скриптов, выполняемых перед заменой файлов CMS,
 * нужно запустить скрипт с параметром — указывающим папку с новой версией CMS
 *
 *     php update.php /var/www/new-cms
 *
 * В этом случае сначала выполнятся скрипты на основе старой CMS, затем папка
 * обновляемой CMS заменится на папку новой CMS и будут выполнены скрипты,
 * запускаемые на основе новой CMS.
 */

$mod = 'Ideal-CMS'; // или любое название модуля Articles, Shop и т.д.
$outputEncoding = 'cp1251';

// Запрещаем вызов скрипта из браузера
if (isset($_SERVER['REQUEST_URI'])) {
    die('Недопустимое действие');
}

// Определяем папку админки
$cms = dirname(dirname($_SERVER['PHP_SELF']));

// Определяем папку, где находится устанавливаемое обновление
$modDir = ($mod === 'Ideal-CMS') ? $cms : dirname($cms) . '/' . "Mods" . '/' . $mod;
$setupDir = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : $modDir;
define('SETUP_DIR', $setupDir);

// Устанавливаем корневую папку сайта для инициализации окружения
$documentRoot = dirname(dirname($cms));
putenv('SITE_ROOT=' . $documentRoot);

// Признак того, что нам не нужно запускать FrontController, а только инициализировать окружение
$isConsole = true;

// Инициализируем окружение
/** @noinspection PhpIncludeInspection */
require_once $documentRoot . '/_.php';

$config = \Ideal\Core\Config::getInstance();

// Ошибки будем отображать напрямую
$cms = $config->cms;
$cms['errorLog'] = 'display';
$config->cms = $cms;

$versions = new \Ideal\Structure\Service\UpdateCms\Versions();

// Получаем текущую версию из файла логов и файла README.md
$nowVersions = $versions->getVersions();
$nowVersionsFromReadme = $versions->getVersionFromReadme(array($mod => $setupDir));

// Инициализируем модель обновления
$updateCmsModel = new \Ideal\Structure\Service\UpdateCms\Model();

// Устанавливаем признак тестовго режима
$updateCmsModel->setTestMode(true);

// Устанавливаем данные обновления ядра
$updateCmsModel->setUpdate($mod, $nowVersionsFromReadme[$mod], $nowVersions[$mod]);

// Получаем список скриптов для обновления
$scripts = $updateCmsModel->getUpdateScripts();

// Если указана отдельная папка с дистрибутивом
if (SETUP_DIR !== $modDir) {
    // Запускаем скрипты обновления до замены файлов CMS
    foreach ($scripts['pre'] as $script) {
        $updateCmsModel->runScript($script);
    }
    $updateCmsModel->swapUpdate();
} else {
    echo mb_convert_encoding(
            'Скрипт запущен без указания папки обновления. Запускаем только скрипты new_*',
            $outputEncoding)
        . "\n";
}

// Запускаем скрипты обновления после замены файлов CMS
foreach ($scripts['after'] as $script) {
    $updateCmsModel->runScript($script);
}

// Получаем ответы от запуска скриптов
$answers = $updateCmsModel->getAnswer();

// Выводим сообщения
foreach ($answers['message'] as $value) {
    echo mb_convert_encoding($value[0], $outputEncoding) . "\n";
}
