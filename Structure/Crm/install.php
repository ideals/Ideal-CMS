<?php
// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

$config = Ideal\Core\Config::getInstance();

$cfg = $config->getStructureByName('Ideal_Crm');

$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';

$_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
$max = $db->select($_sql);
$newPos = intval($max[0]['maxPos']) + 1;

// Создание таблицы для справочника
$db->create($config->db['prefix'] . 'ideal_structure_crm', $cfg['fields']);

// Добавление тестового заказчика
$db->insert($config->db['prefix'] . 'ideal_structure_crm', array('phone' => '00000000000'));

$db->insert(
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
