<?php
/**
 * Добавление счётчика неудачных попыток авторизации для таблицы пользователя
 */

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();

// Создаём подключение к БД
$dbConf = $config['db'];
$db = \Ideal\Core\Db::getInstance();

$table = $dbConf['prefix'] . 'ideal_structure_user';
$sql = "ALTER TABLE {$table} ADD counter_failures int(11) DEFAULT '0' NOT NULL AFTER is_active";
$db->query($sql);
