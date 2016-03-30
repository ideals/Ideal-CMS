<?php
// Создаём таблицу хранящую информацию о правах пользователей.
use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

$table = $config->db['prefix'] . 'ideal_service_acl';
$fields = array(
    'user_id' => array(
        'label' => 'Идентификатор пользователя',
        'sql' => 'int(11) NOT NULL',
    ),
    'structure' => array(
        'label' => 'Обозначение определённого элемента структуры',
        'sql' => 'varchar(255) NOT NULL',
    ),
    'show' => array(
        'label' => 'Показывать',
        'sql' => "bool DEFAULT '1' NOT NULL",
    ),
    'edit' => array(
        'label' => 'Редактировать',
        'sql' => "bool DEFAULT '1' NOT NULL",
    ),
    'delete' => array(
        'label' => 'Удалять',
        'sql' => "bool DEFAULT '1' NOT NULL",
    ),
    'enter' => array(
        'label' => 'Входить',
        'sql' => "bool DEFAULT '1' NOT NULL",
    ),
    'edit_children' => array(
        'label' => 'Редактировать дочерние элементы',
        'sql' => "bool DEFAULT '1' NOT NULL",
    ),
    'delete_children' => array(
        'label' => 'Удалять дочерние элементы',
        'sql' => "bool DEFAULT '1' NOT NULL",
    ),
    'enter_children' => array(
        'label' => 'Входить в дочерние элементы',
        'sql' => "bool DEFAULT '1' NOT NULL",
    ),
);
$db->create($table, $fields);
