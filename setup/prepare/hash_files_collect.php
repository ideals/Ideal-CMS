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
$systemFiles = Ideal\Structure\Service\CheckCmsFiles\AjaxController::getAllSystemFiles($cmsFolder, $cmsFolder);

// Записываем данные в файл информации о хэшах файлов системы
if (file_put_contents($cmsFolder . '/setup/prepare/hash_files', serialize($systemFiles))) {
    echo "Информация о хэшах удачно записана\n";
} else {
    echo "Не удалось записать информацию о хэшах\n";
}
