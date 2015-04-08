<?php
/**
 * Добавление счётчика неудачных попыток авторизации для таблицы пользователя
 */

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();

// Создаём подключение к БД
$dbConf = $config['db'];
$db = \Ideal\Core\Db::getInstance();

$sql = "ALTER TABLE {$dbConf['prefix']}ideal_structure_user ADD counter_failures int(11) DEFAULT '0' NOT NULL AFTER is_active";
$db->query($sql);
