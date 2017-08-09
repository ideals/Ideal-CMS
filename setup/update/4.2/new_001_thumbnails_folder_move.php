<?php
// Путь до папки, которую необходимо перенести
$resourceFolder = DOCUMENT_ROOT . DIRECTORY_SEPARATOR . '_thumbs';

// Путь до папки в которую осуществляетс перенос
$destinationFolder = DOCUMENT_ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . '_thumbs';

// Проверяем существует ли папка назначения, и если нет, то пытаемся создать
if (($destExist = is_dir($destinationFolder)) === false) {
    $destExist = mkdir($destinationFolder);
}
if (is_dir($resourceFolder) && $destExist) {
    // Обходим папку источник со всеми вложенностями и переносим в папку назначения с сохранением структуры вложенности
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($resourceFolder, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
        RecursiveIteratorIterator::CATCH_GET_CHILD
    );
    $ok = true;
    $dirsForDelete = array();
    foreach ($iterator as $info) {
        // Берём информацию о пути до рассматриваемого элемента
        // и обрезаем путь до папки "ресурса", чтобы получить относительный путь
        $relativePath = str_replace($resourceFolder, '', $info->getPathname());

        // Если элемент это директория
        if ($info->isDir()) {
            $dirsForDelete[] = $info->getPathname();
            // Проверяем наличие этой директории в папке назначения
            if (!is_dir($destinationFolder . $relativePath)) {
                mkdir($destinationFolder . $relativePath);
            }
        } else {
            // Проверяем нет ли уже такого файла в директории назначения
            if (!is_file($destinationFolder . $relativePath)) {
                if (($ok = rename($info->getPathname(), $destinationFolder . $relativePath)) === false) {
                    break;
                }
            } else {
                unlink($info->getPathname());
            }
        }
    }
    if ($ok) {
        $dirsForDelete = array_reverse($dirsForDelete);
        foreach ($dirsForDelete as $dirForDelete) {
            rmdir($dirForDelete);
        }
        rmdir($resourceFolder);
    }
}
