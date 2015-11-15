<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

if (!isset($_REQUEST['js']) || empty($_REQUEST['js'])) {
    die;
}
require_once('../[[CMS]]/Ideal/Library/Minifier/class.magic-min.php');

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш
$docRoot = getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT'];

$request = $_REQUEST['js'];

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

// Отключаем Google Closure, используем встроенный JShrink
$min = new Minifier(array('closure' => false, 'echo' => false));

// Объединяем, минимизируем и записываем результат в файл /js/all.min.js
$file = $min->merge($docRoot . '/js/all.min.js', '', $request);

// Выводим объединённый и минимизированный результат
header('Content-type: application/javascript');
die (file_get_contents($file));
