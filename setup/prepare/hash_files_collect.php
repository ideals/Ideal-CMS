<?php
$cmsFolder = __DIR__ . '/../..';

// Если передан аргумент содержащий путь до папки CMS, то используем его
if (isset($_SERVER['argv'][1])) {
    $cmsFolder = $_SERVER['argv'][1];
}

// Подключаем файлы, которые нужны для работы класса AjaxController
require $cmsFolder . '/Core/AjaxController.php';

// Подключаем класс проверки целостности файлов
require $cmsFolder . '/Structure/Service/CheckCmsFiles/AjaxController.php';

// Собираем хэши файлов
$scanFolder = $cmsFolder;
$systemFiles = Ideal\Structure\Service\CheckCmsFiles\AjaxController::getAllSystemFiles($scanFolder, $cmsFolder);

// Записываем данные в файл информации о хэшах файлов системы
$file = "<?php\n// @codingStandardsIgnoreFile\nreturn " . var_export($systemFiles, true) . ";\n";
file_put_contents($cmsFolder . '/setup/prepare/hash_files.php', $file);
