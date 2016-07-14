<?php
// Подключаем структуру для хранения групп пользователей.
use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

// 1. Если в config.php отсутствует подключение Ideal_UserGroup - подключаем
$userGroup = $config->getStructureByName('Ideal_UserGroup');
if ($userGroup === false) {
    $userGroupId = 0;
    foreach ($config->structures as $val) {
        if ($val['ID'] > $userGroupId) {
            $userGroupId = $val['ID'];
        }
    }
    $userGroupId++;
    $add = <<<ADD

        // Подключаем справочник групп пользователей
        array(
            'ID' => {$userGroupId},
            'structure' => 'Ideal_UserGroup',
            'name' => 'Группы пользователей',
            'isShow' => 0,
            'hasTable' => true
        ),
ADD;
    $fileName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }
    $file = file_get_contents($fileName);
    $pos = strrpos($file, ',');
    $file = substr($file, 0, $pos + 1) . $add . substr($file, $pos + 1);
    file_put_contents($fileName, $file);
    $config->loadSettings();
}

// 2. Если отсутствует таблица для Ideal_UserGroup - создаем её
$cfg = $config->getStructureByName('Ideal_UserGroup');
$table = $config->db['prefix'] . 'ideal_structure_usergroup';
$sql = "SHOW TABLES LIKE '{$table}'";
$res = $db->select($sql);
if (empty($res)) {
    // Создание таблицы для структуры управления правами пользователей
    $db->create($table, $cfg['fields']);
}

// 3. Если в таблице справочников отсутствует элемент со структурой Ideal_UserGroup - создаем его
$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';
$_sql = "SELECT * FROM {$dataListTable} WHERE structure='Ideal_UserGroup'";
$userGroup = $db->select($_sql);
if (empty($userGroup)) {
    $_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
    $max = $db->select($_sql);
    $newPos = intval($max[0]['maxPos']) + 1;

    // Создаем запись Заказы с сайта в Справочниках
    $db->insert(
        $dataListTable,
        array(
            'prev_structure' => '0-3',
            'structure' => 'Ideal_UserGroup',
            'pos' => $newPos,
            'name' => 'Группы пользователей',
            'url' => 'gruppy-polzovatelej',
            'parent_url' => '---',
            'annot' => ''
        )
    );
}
