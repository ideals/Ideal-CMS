<?php
/**
 * Добавление дополнительного файла для управления кроном
 */

$config = \Ideal\Core\Config::getInstance();
$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/crontab';
if (!file_exists($file)) {
    file_put_contents($file, '');
}
