<?php
// Подключаем структуру для управления списком заказчиков

use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

// 1. Если в config.php отсутствует подключение Ideal_Crm - подключаем
$crm = $config->getStructureByName('Ideal_Crm');
if ($crm === false) {
    $crmId = 0;
    foreach ($config->structures as $val) {
        if ($val['ID'] > $crmId) {
            $crmId = $val['ID'];
        }
    }
    $crmId++;
    $add = <<<ADD

        // Подключаем справочник заказчиков
        array(
            'ID' => {$crmId},
            'structure' => 'Ideal_Crm',
            'name' => 'Заказчики',
            'isShow' => 0,
            'hasTable' => true
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

// 2. Если в таблице справочников отсутствует элемент со структурой Ideal_Crm - создаем его
$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';
$_sql = "SELECT * FROM {$dataListTable} WHERE structure='Ideal_Crm'";
$crm = $db->select($_sql);
if (empty($crm)) {
    $_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
    $max = $db->select($_sql);
    $newPos = intval($max[0]['maxPos']) + 1;

    // Создаем запись Заказчики с сайта в Справочниках
    $prevStructureId = $db->insert(
        $dataListTable,
        array(
            'prev_structure' => '0-3',
            'structure' => 'Ideal_Crm',
            'pos' => $newPos,
            'name' => 'Заказчики',
            'url' => 'zakazchiki',
            'parent_url' => '---',
            'annot' => ''
        )
    );
} else {
    $prevStructureId = $crm[0]['ID'];
}

// 3. Если отсутствует таблица для Ideal_Crm - создаем её
$cfg = $config->getStructureByName('Ideal_Crm');
$table = $config->db['prefix'] . 'ideal_structure_crm';
$sql = "SHOW TABLES LIKE '{$table}'";
$res = $db->select($sql);
if (empty($res)) {
    // Создание таблицы для структуры управления списком заказчиков
    $db->create($table, $cfg['fields']);

    // Добавляем тестового заказчика
    $db->insert(
        $config->db['prefix'] . 'ideal_structure_crm',
        array('phone' => '00000000000', 'date_create' => time(), 'prev_structure' => '3-' . $prevStructureId, 'name' => 'test')
    );
}
