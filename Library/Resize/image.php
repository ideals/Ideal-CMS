<?php
/**
 * Скрипт изменения размеров изображения. Вызывается с помощью .htaccess
 */
use Resize\Resize;

if (!isset($_GET['img']) || $_GET['img'] == '') {
    // Если не указан параметр содержащий адрес оригинального изображения
    header("HTTP/1.x 404 Not Found");
    exit;
}

include('Resize.php');

$r = new Resize();

$r->run($_GET['img']);
