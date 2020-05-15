<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2020 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

if (!isset($_REQUEST['js']) || empty($_REQUEST['js'])) {
    die;
}

require_once('../../vendor/autoload.php');

use MatthiasMullie\Minify;

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш
$docRoot = getenv('SITE_ROOT') ?: $_SERVER['DOCUMENT_ROOT'];

$request = $_REQUEST['js'];

$minifier = new Minify\JS();

foreach ($request as $file) {
    $file = trim($file);
    if (strpos($file, 'http') !== 0) {
        // Убираем лишние пробелы из путей и добавляем путь к корню сайта на диске
        $file = $docRoot . '/' . ltrim($file, '/');
    }
    $minifier->add($file);
}

// Объединяем, минимизируем и записываем результат в файл /js/all.min.js
$saveFile = $docRoot . '/css/all.min.css';
$minifier->minify($saveFile);

// Выводим объединённый и минимизированный результат
header('Content-type: application/javascript');
die(file_get_contents($saveFile));
