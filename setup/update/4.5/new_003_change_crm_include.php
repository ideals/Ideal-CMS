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

    $configFile = include($fileName);

    // Ищем структуру Ideal_Crm
    foreach ($configFile['structures'] as $key => $structure) {
        if ($structure['structure'] == 'Ideal_Crm') {
            $configFile['structures'][$key]['isShow'] = 1;
            $configFile['structures'][$key]['hasTable'] = false;
        }
    }

    file_put_contents($fileName, "<?php \n return " . var_export($configFile, 1) . ";\n");
}
