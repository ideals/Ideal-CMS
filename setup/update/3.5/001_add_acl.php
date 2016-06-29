<?php
// Подключаем структуру для управления правами пользователей.
use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

// 1. Если в config.php отсутствует подключение Ideal_Acl - подключаем
$acl = $config->getStructureByName('Ideal_Acl');
if ($acl === false) {
    $aclId = 0;
    foreach ($config->structures as $val) {
        if ($val['ID'] > $aclId) {
            $aclId = $val['ID'];
        }
    }
    $aclId++;
    $add = <<<ADD

        // Подключаем структуру управления пользователями
        array(
            'ID' => {$aclId},
            'structure' => 'Ideal_Acl',
            'name' => 'Права пользователей',
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

// 2. Если отсутствует таблица для Ideal_Acl - создаем её
$cfg = $config->getStructureByName('Ideal_Acl');
$table = $config->db['prefix'] . 'ideal_structure_acl';
$sql = "SHOW TABLES LIKE '{$table}'";
$res = $db->select($sql);
if (empty($res)) {
    // Создание таблицы для структуры управления правами пользователей
    $db->create($table, $cfg['fields']);
}
