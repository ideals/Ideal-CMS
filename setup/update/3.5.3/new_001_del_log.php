<?php
$path = getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT'];
$isConsole = true;
require_once $path . '/_.php';

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();

// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

// 1. Удаляем таблицу с логами из базы данных
$table = $config->db['prefix'] . 'ideal_structure_log';
$sql = "SHOW TABLES LIKE '{$table}';";
$res = $db->select($sql);
if (!empty($res)) {
    $sql = "DROP TABLE `{$table}`;";
    $db->query($sql);
}

// 2. Удаляем Логи из списка справочников
$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';
$_sql = "SELECT * FROM {$dataListTable} WHERE structure='Ideal_Log';";
$log = $db->select($_sql);
if (!empty($log)) {
    $sql = "DELETE FROM {$dataListTable} WHERE structure='Ideal_Log';";
    $db->query($sql);
}

// 3. Если в config.php есть подключение Ideal_Log - удаляем
$log = $config->getStructureByName('Ideal_Log');
if ($log !== false) {
    $fileName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }
    $file = file_get_contents($fileName);
    $pos = strrpos($file, "'ID' => {$log['ID']},");
    $firstFilePart = substr($file, 0, $pos);
    $secondFilePart = substr($file, $pos);
    // Вычищаем всё что относится к логам выше найденного идентификатора
    $pos = strrpos($firstFilePart, 'array(');
    $firstFilePart = substr($firstFilePart, 0, $pos);
    // Вычищаем всё что относится к логам ниже найденного идентификатора
    $pos = strpos($secondFilePart, '),');
    $secondFilePart = substr($secondFilePart, $pos + 2);
    $cleanFileContent = $firstFilePart . $secondFilePart;
    file_put_contents($fileName, $cleanFileContent);
    $config->loadSettings();
}
