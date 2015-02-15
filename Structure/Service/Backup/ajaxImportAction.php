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
    // получаем массив строк .sql файла из GZIP архива
    $sql_list = gzfile($dumpName);
    
    // Строка с запросами, разделенными ";"
    $query = '';

    foreach ($sql_list as $str) {
        if (! preg_match('/^\-\-(.*)$/ui', $str)) {
            if (preg_match('/(SET|CREATE TABLE|INSERT INTO|DROP|UPDATE |ALTER TABLE|LOCK|UNLOCK)/is', $str)) {
                $sql = trim($sql);
                if ($sql) {
                    $sql = str_replace("DEFAULT 'CURRENT_TIMESTAMP'", "DEFAULT CURRENT_TIMESTAMP", $sql);                    
                    $query .= $sql;
                }
                $sql = $str;
            } else {
                $sql .= $str;
            }
        }
    }
    if ($sql != '') {
        $query .= $sql;
    }
    // Выполняем запросы
    if ($db->multi_query($query)) {
        do {
            $db->next_result();
        } while( $db->more_results() ); 
    } 
    
    if (! $db->errno) {
        exit(true);
    } else {
        echo $db->error;
        exit();
    }    
}

exit(false);
