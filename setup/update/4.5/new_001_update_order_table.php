<?php
// Актуализируем таблицу структуры заказа

use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

// Сканируем таблицу структуры заказов
$orderStructure = $config->getStructureByName('Iedal_Order');
if ($orderStructure) {
    $orderTable = $config->getTableByName('Iedal_Order');
    $fieldsInfo = $db->select('SHOW COLUMNS FROM ' . $orderTable . ' FROM `' . $config->db['name'] . '`');
    $fields = array();
    array_walk($fieldsInfo, function ($v) use (&$fields) {
        $fields[$v['Field']] = $v['Type'];
    });

    // Если нет поля "Телефон", то добавляем его в таблицу заказов
    if (!isset($fields['phone'])) {
        $sql = "ALTER TABLE {$orderTable} ADD phone text COMMENT 'Телефон' AFTER email;";
        $db->query($sql);
    }

    // Если нет поля "Client ID", то добавляем его в таблицу заказов
    if (!isset($fields['client_id'])) {
        $sql = "ALTER TABLE {$orderTable} ADD client_id text COMMENT 'Client ID' AFTER phone;";
        $db->query($sql);
    }

    // Если нет поля "Контактное лицо", то добавляем его в таблицу заказов
    if (!isset($fields['contact_person']) && !isset($fields['customer'])) {
        $sql = "ALTER TABLE {$orderTable} ADD contact_person int(8) COMMENT 'Контактное лицо' AFTER order_type;";
        $db->query($sql);
    } elseif (isset($fields['customer'])) {
        // Или меняем поле "Заказчик" на поле "Контактное лицо"
        $sql = "ALTER TABLE {$orderTable} CHANGE customer contact_person int(8) COMMENT 'Контактное лицо';";
        $db->query($sql);
    }
}
