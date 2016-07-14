<?php
// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

$config = Ideal\Core\Config::getInstance();

$cfg = $config->getStructureByName('Ideal_Log');

$dataListTable = $config->db['prefix'] . 'ideal_structure_log';

$_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
$max = $db->select($_sql);
$newPos = intval($max[0]['maxPos']) + 1;

// Создание таблицы для справочника
$db->create($config->db['prefix'] . 'ideal_structure_log', $cfg['fields']);

$db->insert(
    $dataListTable,
    array(
        'prev_structure' => '0-3',
        'structure' => 'Ideal_Log',
        'pos' => $newPos,
        'name' => 'Логи',
        'url' => 'logi',
        'parent_url' => '---',
        'annot' => ''
    )
);
