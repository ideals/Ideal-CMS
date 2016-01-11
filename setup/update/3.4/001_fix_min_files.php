<?php
// Обновляем файлы "min.gen.php" для css и js
use Ideal\Core\Config;

$config = Config::getInstance();

$cssMinFalse = DOCUMENT_ROOT . '/css/min.gen.php';
$jsMinFalse = DOCUMENT_ROOT . '/js/min.gen.php';

$cssMinTrue = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/setup/front/css/min.gen.php';
$jsMinTrue = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/setup/front/js/min.gen.php';

$file = file_get_contents($cssMinTrue);
$file = substr_replace('[[CMS]]', $config->cmsFolder, $file);
if (!file_put_contents($cssMinFalse, $file)) {
    echo "не удалось заменить файл $cssMinFalse\n";
}

$file = file_get_contents($jsMinTrue);
$file = substr_replace('[[CMS]]', $config->cmsFolder, $file);
if (!file_put_contents($jsMinFalse, $file)) {
    echo "не удалось заменить файл $jsMinFalse\n";
}
