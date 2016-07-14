<?php
// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

$cfg = $config->getStructureByName('Ideal_Acl');

// Создание таблицы для структуры управления правами пользователя
$db->create($config->db['prefix'] . 'ideal_structure_acl', $cfg['fields']);
