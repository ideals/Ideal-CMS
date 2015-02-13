<?php
/**
 * Импортируем дамп БД
 */

// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

// Файл дампа БД
$dumpName = addslashes(stream_resolve_include_path($_POST['name']));

if (file_exists($dumpName)) {
    $sql = '';
    // получаем массив строк sql файла из архива .gz
    $sql_list = gzfile($dumpName);

    foreach ($sql_list as $str) {
        if (! preg_match('/^\-\-(.*)$/ui', $str)) {
            if (preg_match('/(SET|CREATE TABLE|INSERT INTO|DROP|UPDATE |ALTER TABLE|LOCK|UNLOCK)/is', $str)) {
                if (trim($sql)) {
                    $sql = str_replace("DEFAULT 'CURRENT_TIMESTAMP'", "DEFAULT CURRENT_TIMESTAMP", $sql);
                    $db->query($sql);
                }
                $sql = $str;
            } else {
                $sql .= $str;
            }
        }
    }
    if ($sql != '') {
        $db->query($sql);
    }
    exit(true);
}

exit(false);
