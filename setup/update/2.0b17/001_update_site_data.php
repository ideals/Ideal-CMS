<?php
/**
 * Пересохранение конфигурационного файла site_data.php, чтобы значения настроек были в двойных кавычках
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$configSD->saveFile($file);
