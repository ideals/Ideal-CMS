<?php

// 1. Если в config.php отсутствует структура справочников, добавляем её
$config = \Ideal\Core\Config::getInstance();
$directory = $config->getStructureByName('Ideal_DataList');

if ($directory === false) {
    $ID = count($config->structures) + 1;
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
    $fileName = CMS_ROOT . '/config.php';
    $file = file_get_contents($fileName);

    $pos = strrpos($file, ',');

    $file = substr($file, 0, $pos + 1) . $add . substr($file, $pos + 1);

    file_put_contents($fileName, $file);
}

// 2. Если отсутствует таблица справочников - создаем её

// Создаём подключение к БД
$dbConf = $config->db;
$db = \Ideal\Core\Db::getInstance();

$table = $dbConf['prefix'] . 'ideal_structure_datalist';

$res = $db->select("SHOW TABLES LIKE '{$table}'");

// Если таблицы ideal_structure_datalist не существует - создаем её
if (empty($res)) {
    // Дописать считывание Structure/Datalist/config.php и создание таблицы
    $filename = CMS_ROOT . '/Ideal/Structure/DataList/config.php';
    $file = require($filename);
    $db->create($table, $file['fields']);
}

// 3. Если в таблице справочников отсутствует элемент со структурой Ideal_Order - создаем его

$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';

$_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
$max = $db->select($_sql);
$_sql = "SELECT * FROM {$dataListTable} WHERE structure='Ideal_Order'";
$order = $db->select($_sql);

// Создаем запись Заказы с сайта в Справочниках
if ($max[0]['maxPos'] == null || $order == null) {
    $newPos = intval($max[0]['maxPos']) + 1;
    $conf = $config->getStructureByName('Ideal_DataList');
    $db->insert(
        $dataListTable,
        array(
            'prev_structure' => "0-{$conf['ID']}",
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

$table = $config->db['prefix'] . 'ideal_structure_order';
$sql = "SHOW TABLES LIKE '{$table}'";
$res = $db->select($sql);
if (empty($res)) {
    // Создание таблицы для справочника
    $db->create($table, $cfg['fields']);
}
