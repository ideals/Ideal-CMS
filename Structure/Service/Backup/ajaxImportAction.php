<?php
/**
 * Импортируем дамп БД
 */

// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

// Файл дампа БД
$dumpName = addslashes(stream_resolve_include_path($_POST['name']));

if (!file_exists($dumpName)) {
    echo "Не найден файл: " . basename($_POST['name']);
    exit;
}

// Получаем массив строк .sql файла из GZIP архива
$str_list = gzfile($dumpName);

// Строка с запросами, разделенными ";"
$query = implode('', $str_list);

// Выполняем запросы
if ($db->multi_query($query)) {
    do {
        $db->next_result();
    } while ($db->more_results());
}

// Выводим ошибки MySQL, если были
if ($db->errno) {
    echo $db->error;
}

exit;
