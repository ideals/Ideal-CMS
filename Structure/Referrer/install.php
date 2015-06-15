<?php
// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

$config = Ideal\Core\Config::getInstance();

$cfg = $config->getStructureByName('Ideal_Referrer');

$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';

// Создание таблицы для справочника
$db->create($config->db['prefix'] . 'i_ideal_structure_referrer', $cfg['fields']);

$db->insert(
    $dataListTable,
    array(
        'ID' => 1,
        'prev_structure' => '0-3',
        'structure' => 'Ideal_Referrer',
        'pos' => '1',
        'name' => 'Источник перехода',
        'url' => 'istochnik-perehoda',
        'parent_url' => '/',
        'annot' => ''
    )
);
