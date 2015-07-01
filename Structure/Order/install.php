<?php
// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

$config = Ideal\Core\Config::getInstance();

$cfg = $config->getStructureByName('Ideal_Order');

$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';

$_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
$max = $db->select($_sql);
$newPos = intval($max[0]['maxPos']) + 1;

// Создание таблицы для справочника
$db->create($config->db['prefix'] . 'ideal_structure_order', $cfg['fields']);

$db->insert(
    $dataListTable,
    array(
        'prev_structure' => '0-3',
        'structure' => 'Ideal_Order',
        'pos' => $newPos,
        'name' => 'Заказы с сайта',
        'url' => 'zakazy-s-sajta',
        'parent_url' => '---',
        'annot' => ''
    )
);
