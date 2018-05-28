<?php
// Изменяем подключение структуры CRM

use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

$crmStructure = $config->getStructureByName('Ideal_Crm');
if ($crmStructure) {
    // Изменяем подключение структуры Ideal_Crm
    $fileName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }
    $file = file_get_contents($fileName);

    // Локализум место подключения структуры CRM
    $pos = strrpos($file, 'Ideal_Crm');
    $fileTmp = substr($file, 0, $pos);
    $fileTmp2 = substr($file, $pos);

    // Позиция начала описания включения структуры CRM
    $posCrmStart = strrpos($fileTmp, 'array(');

    // Позиция окончания описания включения структуры CRM
    $posCrmEnd = strpos($fileTmp2, 'array(');

    // Вырезаем из основного файла подключения структуры CRM
    $file = substr($fileTmp, 0, $posCrmStart) . substr($fileTmp2, $posCrmEnd);

    // Возвращаем изменённое подключение структуры CRM
    $add = <<<ADD
        // Подключаем структуру CRM
        array(
            'ID' => {$crmStructure['ID']},
            'structure' => 'Ideal_Crm',
            'name' => 'CRM',
            'isShow' => 1,
            'hasTable' => false,
        ),
ADD;
    $pos = strrpos($file, ',');
    $file = substr($file, 0, $pos + 1) . $add . substr($file, $pos + 1);
    file_put_contents($fileName, $file);
    $config->loadSettings();
}
