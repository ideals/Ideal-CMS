<?php
// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

$cfg = $config->getStructureByName('Ideal_DataList');

// Создание таблицы для справочника
$db->create($config->db['prefix'] . 'ideal_structure_datalist', $cfg['fields']);
