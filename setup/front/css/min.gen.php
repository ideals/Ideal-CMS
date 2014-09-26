<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

if (!isset($_REQUEST['css']) || empty($_REQUEST['css'])) {
    die;
}
require_once('../[[CMS]]/Ideal/Library/Minifier/Minifier.php');

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш.
define('DOCUMENT_ROOT', getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT']);

$request = $_REQUEST['css'];

// Убираем лишние пробелы из путей и добавляем путь к корню сайта на диске
array_walk(
    $request,
    function (&$v) {
        $v = DOCUMENT_ROOT . '/' . trim($v);
    }
);

// Объединяем, минимизируем и записываем результат в файл /css/all.min.css
$min = new Minifier();
$file = $min->merge(DOCUMENT_ROOT . '/css/all.min.css', '', $request);

// Выводим объединённый и минимизированный результат
header('Content-type: text/css');
die (file_get_contents($file));
