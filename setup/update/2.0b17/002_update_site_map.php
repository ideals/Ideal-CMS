<?php
/**
 * Пересохранение конфигурационного файла site_map.php, чтобы значения настроек были в двойных кавычках
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_map.php';

if (file_exists($file)) {
    $configSD->loadFile($file);
    $configSD->saveFile($file);
}
