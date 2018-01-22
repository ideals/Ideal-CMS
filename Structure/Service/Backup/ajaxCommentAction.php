<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

/**
 * Комментарий для дампа БД
 */

// Файл дампа БД
$dumpName = addslashes(stream_resolve_include_path($_POST['name']));

// Действие над комментарием
$act = htmlspecialchars($_POST['act']);

// Имя файла с комментарием
$cmtName = str_replace(".gz", ".txt", $dumpName);

$cmtText = '';  // текст комментария

switch ($act) {
    // Получаем комментарий для вставки в textarea
    case 'get':
        if (file_exists($cmtName)) {
            $cmtText = file_get_contents($cmtName);
        }
        echo $cmtText;
        break;
    // Сохраняем комментарий в файле
    case 'save':
        $cmtText = $_POST['text'];
        file_put_contents($cmtName, $cmtText);
        echo $cmtText;
        break;
}

exit;
