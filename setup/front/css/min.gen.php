<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2020 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

if (!isset($_REQUEST['css']) || empty($_REQUEST['css'])) {
    die;
}

require_once('../../vendor/autoload.php');

use MatthiasMullie\Minify;

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш.
$docRoot = getenv('SITE_ROOT') ?: $_SERVER['DOCUMENT_ROOT'];

$request = $_REQUEST['css'];

$minifier = new Minify\CSS();

foreach ($request as $file) {
    $file = trim($file);
    if (strpos($file, 'http') !== 0) {
        // Убираем лишние пробелы из путей и добавляем путь к корню сайта на диске
        $file = $docRoot . '/' . ltrim($file, '/');
    } else {
        // Это ссылка на файл в интернете, получаем его сразу же
        $file = file_get_contents($file);
    }
    $minifier->add($file);
}

// Объединяем, минимизируем и записываем результат в файл /css/all.min.css
$saveFile = $docRoot . '/css/all.min.css';
$minifier->minify($saveFile);

// Выводим объединённый и минимизированный результат
header('Content-type: text/css');
die(file_get_contents($saveFile));
