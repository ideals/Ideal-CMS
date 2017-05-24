<?php
// Модифицируем конфигурацию и базу данных структуры пользователя.
use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

$cmsFolder = $config->cmsFolder;

// 1. Если config.php для структуры "Order" переопределён и в нём отсутствует поле "customer", то добавляем его туда
$filename = DOCUMENT_ROOT . '/' . $cmsFolder . '/Ideal.c/Structure/Order/config.php';
if (file_exists($filename)) {
    $orderConfig = require($filename);
    if (!isset($orderConfig['fields']['customer'])) {
        $orderConfig['fields']['customer'] = array(
            'label' => 'Заказчик',
            'sql' => 'int(8)',
            'type' => 'Ideal_Select',
            'medium' => '\\Ideal\\Medium\\CustomerList\\Model'
        );
        file_put_contents($filename, '<?php return ' . var_export($orderConfig, true) . ";\n");
    }
}

// 2. Если в таблице структуры "Ideal_Order" нет поля "customer", то добавляем его туда.
$orderTable = $config->getTableByName('Ideal_Order');
// Получаем информацию о полях таблицы
$fieldsInfo = $db->select('SHOW COLUMNS FROM ' . $orderTable . ' FROM `' . $config->db['name'] . '`');
$fields = array();
array_walk($fieldsInfo, function ($v) use (&$fields) {
    $fields[] = $v['Field'];
});
if (array_search('customer', $fields) === false) {
    $sql = "ALTER TABLE {$orderTable} ADD customer int(8) COMMENT 'Заказчик';";
    $db->query($sql);
}
