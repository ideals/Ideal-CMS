<?php
/*
 * Удаление файла
 */

if (!isset($_POST['name'])) {
    echo 'Ошибка: нет имени файла для удаления';
}

$dumpName = stream_resolve_include_path($_POST['name']);
$cmtName = str_replace('.gz', '.txt', $dumpName);
// Удаляем файл дампа БД
if (file_exists($dumpName)) {
    unlink($dumpName);
}
// Удаляем файл комментария
if (file_exists($cmtName)) {
    unlink($cmtName);
}

exit;
