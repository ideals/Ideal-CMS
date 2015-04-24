<?php
$cmsFolder = __DIR__ . '/../../..';

// Подключаем файлы, которые нужны для работы класса AjaxController
require $cmsFolder . '/Ideal/Core/AjaxController.php';

// Подключаем класс проверки целостности файлов
require $cmsFolder . '/Ideal/Structure/Service/CheckCmsFiles/AjaxController.php';

// Собираем хэши файлов
$systemFiles = Ideal\Structure\Service\CheckCmsFiles\AjaxController::getAllSystemFiles($cmsFolder . '/Ideal', $cmsFolder);

// Записываем данные в файл информации о хэшах файлов системы
$file = "<?php\n// @codingStandardsIgnoreFile\nreturn " . var_export($systemFiles, true) . ";\n";
file_put_contents($cmsFolder . '/Ideal/setup/prepare/hash_files.php', $file);
