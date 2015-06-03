<?php
$cmsFolder = stream_resolve_include_path(__DIR__ . '/../..');

// Если передан аргумент содержащий путь до папки CMS, то используем его
if (isset($_SERVER['argv'][1])) {
    $cmsFolder = $_SERVER['argv'][1];
}

// Подключаем файлы, которые нужны для работы класса AjaxController
require $cmsFolder . '/Core/AjaxController.php';

// Подключаем класс проверки целостности файлов
require $cmsFolder . '/Structure/Service/CheckCmsFiles/AjaxController.php';

// Собираем хэши файлов
$systemFiles = Ideal\Structure\Service\CheckCmsFiles\AjaxController::getAllSystemFiles($cmsFolder, $cmsFolder);

// Записываем данные в файл информации о хэшах файлов системы
$file = stream_resolve_include_path($cmsFolder . '/setup/prepare/hash_files');
if (file_put_contents($file, serialize($systemFiles))) {
    echo "Success!\n";
} else {
    echo "Write error in file {$file} \n";
}
