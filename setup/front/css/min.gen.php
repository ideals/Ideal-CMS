<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

if (!isset($_REQUEST['css']) || empty($_REQUEST['css'])) {
    die;
}
require_once('../[[CMS]]/Ideal/Library/Minifier/class.magic-min.php');

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш.
$docRoot = getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT'];

$request = $_REQUEST['css'];

// Убираем лишние пробелы из путей и добавляем путь к корню сайта на диске
array_walk(
    $request,
    function (&$v, $k, $docRoot) {
        $v = trim($v);
        if (strpos($v, 'http') !== 0) {
            $v = $docRoot . '/' . ltrim($v, '/');
        }
    }
);

// Объединяем, минимизируем и записываем результат в файл /css/all.min.css
$min = new Minifier(array('echo' => false));
$file = $min->merge($docRoot . '/css/all.min.css', '', $request);

// Выводим объединённый и минимизированный результат
header('Content-type: text/css');
die (file_get_contents($file));
