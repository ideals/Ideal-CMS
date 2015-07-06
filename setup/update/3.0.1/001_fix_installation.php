<?php

// 1. Если в config.php отсутствует структура справочников, добавляем её
$config = \Ideal\Core\Config::getInstance();
$cmsDir = DOCUMENT_ROOT . '/' . $config->cmsFolder;

$structure = $config->getStructureByName('Ideal_DataList');
if ($structure === false) {
    $ID = 0;
    foreach ($config->structures as $val) {
        if ($val['ID'] > $ID) {
            $ID = $val['ID'];
        }
    }
    $ID++;
    $add = <<<ADD

        // Подключаем справочники
        array(
            'ID' => {$ID},
            'structure' => 'Ideal_DataList',
            'name' => 'Справочники',
            'isShow' => 1,
            'hasTable' => true
        ),
ADD;
    $fileName = $cmsDir . '/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }
    $file = file_get_contents($fileName);

    $pos = strrpos($file, ',');

    $file = substr($file, 0, $pos + 1) . $add . substr($file, $pos + 1);

    file_put_contents($fileName, $file);

    $config->loadSettings();
} else {
    $ID = $structure['ID'];
    if ($structure['isShow'] == 0) {
        $fileName = $cmsDir . '/config.php';
        if (!file_exists($fileName)) {
            throw new \Exception('Файл не найден: ' . $fileName);
        }
        $file = file_get_contents($fileName);
        $pattern = 'Справочники\',(\r*)\n(\s*)\'isShow\'(\s*)=>(\s*)(\d)';
        $replacement = "Справочники',\\1\n\\2'isShow'\\3=>\\41";
        $file = mb_ereg_replace($pattern, $replacement, $file);
        file_put_contents($fileName, $file);
    }
}

// 2. Если отсутствует таблица справочников - создаем её

// Создаём подключение к БД
$dbConf = $config->db;
$db = \Ideal\Core\Db::getInstance();

$table = $dbConf['prefix'] . 'ideal_structure_datalist';

$res = $db->select("SHOW TABLES LIKE '{$table}'");

// Если таблицы ideal_structure_datalist не существует - создаем её
if (empty($res)) {
    $fileName = $cmsDir . '/Ideal/Structure/DataList/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }
    /** @noinspection PhpIncludeInspection */
    $file = require($fileName);
    $db->create($table, $file['fields']);
}

// 3. Если в config.php отсутствует подключение Ideal_Order - подключаем
$order = $config->getStructureByName('Ideal_Order');
if ($order === false) {
    $orderId = 0;
    foreach ($config->structures as $val) {
        if ($val['ID'] > $orderId) {
            $orderId = $val['ID'];
        }
    }
    $orderId++;
    $add = <<<ADD

        // Подключаем заказы
        array(
            'ID' => {$orderId},
            'structure' => 'Ideal_Order',
            'name' => 'Заказы с сайта',
            'isShow' => 0,
            'hasTable' => true
        ),
ADD;
    $fileName = $cmsDir . '/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }
    $file = file_get_contents($fileName);

    $pos = strrpos($file, ',');

    $file = substr($file, 0, $pos + 1) . $add . substr($file, $pos + 1);

    file_put_contents($fileName, $file);

    $config->loadSettings();
}

// 4. Если в таблице справочников отсутствует элемент со структурой Ideal_Order - создаем его

$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';

$_sql = "SELECT * FROM {$dataListTable} WHERE structure='Ideal_Order'";
$order = $db->select($_sql);
if (empty($order)) {
    $_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
    $max = $db->select($_sql);

    $newPos = intval($max[0]['maxPos']) + 1;
    // Создаем запись Заказы с сайта в Справочниках
    $db->insert(
        $dataListTable,
        array(
            'prev_structure' => "0-{$ID}",
            'structure' => 'Ideal_Order',
            'pos' => $newPos,
            'name' => 'Заказы с сайта',
            'url' => 'zakazy-s-sajta',
            'parent_url' => '---',
            'annot' => ''
        )
    );
}

// 4. Если отсутствует таблица для Ideal_Order - создаем её
$cfg = $config->getStructureByName('Ideal_Order');
$table = $config->db['prefix'] . 'ideal_structure_order';
$sql = "SHOW TABLES LIKE '{$table}'";
$res = $db->select($sql);
if (empty($res)) {
    // Создание таблицы для справочника
    $db->create($table, $cfg['fields']);
}
