<?php
// Модифицируем конфигурацию и базу данных структуры пользователя.
use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

$cmsFolder = $config->cmsFolder;

// 1. Если config.php для структуры "User" переопределён и в нём отсутствует поле "user_group", то добавляем его туда
$filename = DOCUMENT_ROOT . '/' . $cmsFolder . '/Ideal.c/Structure/User/config.php';
if (file_exists($filename)) {
    $userConfig = require($filename);
    if (!isset($userConfig['fields']['user_group'])) {
        $userConfig['fields']['user_group'] = array(
            'label' => 'Группа пользователя',
            'sql' => 'int(8)',
            'type' => 'Ideal_Select',
            'medium' => '\\Ideal\\Medium\\UserGroupList\\Model'
        );
        file_put_contents($filename, '<?php return ' . var_export($userConfig, true) . ";\n");
    }
}

// 2. Если в таблице структуры "Ideal_User" нет поля "user_group", то добавляем его туда.
$userTable = $config->getTableByName('Ideal_User');
// Получаем информацию о полях таблицы
$fieldsInfo = $db->select('SHOW COLUMNS FROM ' . $userTable . ' FROM `' . $config->db['name'] . '`');
$fields = array();
array_walk($fieldsInfo, function ($v) use (&$fields) {
    $fields[] = $v['Field'];
});
if (array_search('user_group', $fields) === false) {
    $sql = "ALTER TABLE {$userTable} ADD user_group int(8) COMMENT 'Группа пользователя';";
    $db->query($sql);
}
