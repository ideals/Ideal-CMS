<?php
// 1. Изменение поля "Сумма заказа" для структуры "Order"
$config = \Ideal\Core\Config::getInstance();
$dbConf = $config->db;
$db = \Ideal\Core\Db::getInstance();

$tableName = $dbConf['prefix'] . 'ideal_structure_order';

$sql = "SHOW COLUMNS FROM $tableName WHERE Field = 'price'";
$res = $db->select($sql);
if (!empty($res)) {
    $sql = "ALTER TABLE $tableName MODIFY price FLOAT;";
    $db->query($sql);
}
