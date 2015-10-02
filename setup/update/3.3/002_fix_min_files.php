<?php
// Обновляем файл "min.gen.php" для css и js
use Ideal\Core\Config;

$config = Config::getInstance();

$cssMinFalse = DOCUMENT_ROOT . '/css/min.gen.php';
$jsMinFalse = DOCUMENT_ROOT . '/js/min.gen.php';

$cssMinTrue = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/setup/front/css/min.gen.php';
$jsMinTrue = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/setup/front/js/min.gen.php';

if (!copy($cssMinTrue, $cssMinFalse)) {
    echo "не удалось заменить файл $cssMinFalse\n";
}

if (!copy($jsMinTrue, $jsMinFalse)) {
    echo "не удалось заменить файл $jsMinFalse\n";
}
