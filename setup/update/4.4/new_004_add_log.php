<?php
// Подключаем структуру логирования

use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

// 1. Если в config.php отсутствует подключение Ideal_Log - подключаем
$log = $config->getStructureByName('Ideal_Log');
if ($log === false) {
    $logId = 0;
    foreach ($config->structures as $val) {
        if ($val['ID'] > $logId) {
            $logId = $val['ID'];
        }
    }
    $logId++;
    $add = <<<ADD

        // Подключаем структуру ведения логов администраторов
        array(
            'ID' => {$logId},
            'structure' => 'Ideal_Log',
            'name' => 'Лог администраторов',
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

// 2. Если в таблице справочников отсутствует элемент со структурой Ideal_Log - создаем его
$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';
$_sql = "SELECT * FROM {$dataListTable} WHERE structure='Ideal_Log'";
$log = $db->select($_sql);
if (empty($log)) {
    $_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
    $max = $db->select($_sql);
    $newPos = intval($max[0]['maxPos']) + 1;

    // Создаем запись "Лог администраторов" с сайта в Справочниках
    $prevStructureId = $db->insert(
        $dataListTable,
        array(
            'prev_structure' => '0-3',
            'structure' => 'Ideal_Log',
            'pos' => $newPos,
            'name' => 'Лог администраторов',
            'url' => 'log-administratorov',
            'parent_url' => '---',
            'annot' => ''
        )
    );
} else {
    $prevStructureId = $log[0]['ID'];
}

// 3. Если отсутствует таблица для Ideal_Log - создаем её
$cfg = $config->getStructureByName('Ideal_Log');
$table = $config->db['prefix'] . 'ideal_structure_log';
$sql = "SHOW TABLES LIKE '{$table}'";
$res = $db->select($sql);
if (empty($res)) {
    // Создание таблицы для структуры управления списком заказчиков
    $db->create($table, $cfg['fields']);
}
