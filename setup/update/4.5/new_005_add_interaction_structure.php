<?php
// Подключаем структуру для получения доступа ко всем взаимодействиям

use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

// Если в config.php отсутствует подключение Ideal_Interaction - подключаем
$interaction = $config->getStructureByName('Ideal_Interaction');
if ($interaction === false) {
    $interactionId = 0;
    foreach ($config->structures as $val) {
        if ($val['ID'] > $interactionId) {
            $interactionId = $val['ID'];
        }
    }
    $interactionId++;
    $add = <<<ADD

        // Подключаем общую структуру Взаимодействий
        array(
            'ID' => {$interactionId},
            'structure' => 'Ideal_Interaction',
            'name' => 'Взаимодействия',
            'isShow' => 0,
            'hasTable' => false
        ),
ADD;
    $fileName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }
    $file = file_get_contents($fileName);
    $pos = strrpos($file, ',');
    $file = substr($file, 0, $pos + 1) . $add . substr($file, $pos + 1);
    file_put_contents($fileName, $file);
    $config->loadSettings();
}
