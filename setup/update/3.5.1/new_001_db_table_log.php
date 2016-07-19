<?php
$path = getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT'];
$isConsole = true;
require_once $path . '/_.php';

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();

// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

// 1. Проверяем наличие талицы для хранения логов
$table = $config->db['prefix'] . 'ideal_structure_log';
$sql = "SHOW TABLES LIKE '{$table}'";
$res = $db->select($sql);
if (!empty($res)) {
    // Проверяем поля на существование перед заменой
    $sql = "SHOW COLUMNS FROM {$table} LIKE 'event_type'";
    $res = $db->select($sql);
    if (!empty($res)) {
        $sql = "ALTER TABLE `{$table}` CHANGE COLUMN `event_type` `type` text NOT NULL;";
        $db->query($sql);
    }
    $sql = "SHOW COLUMNS FROM {$table} LIKE 'what_happened'";
    $res = $db->select($sql);
    if (!empty($res)) {
        $sql = "ALTER TABLE `{$table}` CHANGE COLUMN `what_happened` `message` text NOT NULL;";
        $db->query($sql);
    }
}
