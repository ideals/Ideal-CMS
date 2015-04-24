<?php

// Подключаем файл установки для получения константы содержащей адрес до папки админки
require_once '../install_func.php';

// Подключаем файлы, которые нужны для работы класса AjaxController
require_once CMS_ROOT . '/Ideal/Core/AjaxController.php';

// Подключаем класс проверки целостности файлов
require_once CMS_ROOT . '/Ideal/Structure/Service/CheckCmsFiles/AjaxController.php';

// Собираем хэши файлов
$systemFiles = Ideal\Structure\Service\CheckCmsFiles\AjaxController::getAllSystemFiles(CMS_ROOT . '/Ideal', CMS_ROOT);

// Записываем данные в файл информации о хэшах файлов системы
$file = "<?php\n// @codingStandardsIgnoreFile\nreturn " . var_export($systemFiles, true) . ";\n";
file_put_contents(CMS_ROOT . '/Ideal/setup/prepare/hash_files.php', $file);
