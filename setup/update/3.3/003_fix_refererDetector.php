<?php
// Обновляем файл "/js/jsFlashCookies/refererDetector.js"
use Ideal\Core\Config;

$config = Config::getInstance();
$refererDetecterFile = DOCUMENT_ROOT . '/js/jsFlashCookies/refererDetector.js';
$refererDetecterFileTrue = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/Library/jsFlashCookies/refererDetector.js';
if (!copy($refererDetecterFileTrue, $refererDetecterFile)) {
    echo "не удалось заменить файл $refererDetecterFile\n";
}
