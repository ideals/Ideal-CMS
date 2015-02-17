<?php
/**
 * Скрипт изменения размеров изображения. Вызывается с помощью .htaccess
 */

include('Resize.php');
use Resize\Resize;

if (!isset($_GET['img']) || $_GET['img'] == '') {
    // Если не указан параметр содержащий адрес оригинального изображения
    header("HTTP/1.x 404 Not Found");
    exit;
}

$r = new Resize();

$r->run($_GET['img']);
