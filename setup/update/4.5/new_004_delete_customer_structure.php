<?php
// Удаляем структуру заказчика

use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

$crmStructure = $config->getStructureByName('Ideal_Customer');
if ($crmStructure) {
    // Изменяем подключение структуры Ideal_Crm
    $fileName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }

    $configFile = include($fileName);

    // Удаляем подключение структуры Ideal_Customer
    foreach ($configFile['structures'] as $key => $structure) {
        if ($structure['structure'] == 'Ideal_Customer') {
            unset($configFile['structures'][$key]);
            break;
        }
    }

    file_put_contents($fileName, "<?php \n return " . var_export($configFile, 1) . ";\n");

    // Удаляем таблицу для структуры Ideal_Customer
    $customerTable = $config->getTableByName('Ideal_Customer');
    $sql = "SHOW TABLES LIKE '{$customerTable}'";
    $customerRes = $db->select($sql);
    if (!empty($customerRes)) {
        $db->query('DROP TABLE ' . $customerTable . ';');
    }
}
