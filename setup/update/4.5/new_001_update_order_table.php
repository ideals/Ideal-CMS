<?php
// Актуализируем таблицу структуры заказа

use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

// Сканируем таблицу структуры заказов
$orderStructure = $config->getStructureByName('Ideal_Order');
if ($orderStructure) {
    $orderTable = $config->getTableByName('Ideal_Order');
    $fieldsInfo = $db->select('SHOW COLUMNS FROM ' . $orderTable . ' FROM `' . $config->db['name'] . '`');
    $fields = array();
    array_walk($fieldsInfo, function ($v) use (&$fields) {
        $fields[$v['Field']] = $v['Type'];
    });

    // Если есть поле "Заказчик", то удаляем его, т.к. связь с заказчиком будет осуществляться другим способом
    if (isset($fields['customer'])) {
        $sql = "ALTER TABLE {$orderTable} DROP COLUMN customer";
        $db->query($sql);
    }
}
