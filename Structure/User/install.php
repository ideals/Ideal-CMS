<?php
// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();;

$cfg = $config->getStructureByName('Ideal_User');

// Создание таблицы для страниц
$db->create($config->db['prefix'] . 'ideal_structure_user', $cfg['fields']);