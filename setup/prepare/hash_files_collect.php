<?php

// Подключаем файл установки для получения константы содержащей адрес до папки админки
require_once '../install_func.php';

// Инициаируем сбор данных
$systemFiles = getAllSystemFiles(CMS_ROOT . '/Ideal');

// Записываем данные в файл информации о хэшах файлов системы
$file = "<?php\n// @codingStandardsIgnoreFile\nreturn " . var_export($systemFiles, true) . ";\n";

// TODO добавить проверку на существование файла
file_put_contents(CMS_ROOT . '/Ideal/setup/prepare/hash_files.php', $file);

/**
 * @param string $folder Путь до сканируемой попки
 * @return array Массив где ключами являются пути до файлов, а значениями их хэши
 */
function getAllSystemFiles($folder)
{
    $systemFiles = array();
    $files = scandir($folder);
    foreach ($files as $file) {
        // Отбрасываем не нужные каталоги и файлы
        if (preg_match('/^\..*?|hash_files\.php$/isU', $file)) {
            continue;
        }
        // Если директория, то запускаем сбор внутри директории
        if (is_dir($folder . '/' . $file)) {
            $systemFiles = array_merge($systemFiles, getAllSystemFiles($folder . '/' . $file));
        } else {
            $fileKeyArray = str_replace(CMS_ROOT . '/', '', $folder) . '/' . $file;
            $systemFiles[$fileKeyArray] = hash_file('crc32b', $folder . '/' . $file);
        }
    }
    return $systemFiles;
}
