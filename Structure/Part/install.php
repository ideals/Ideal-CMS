<?php
// Инициализируем доступ к БД
$db = \Ideal\Core\Db::getInstance();

$cfg = $config->getStructureByName('Ideal_Part');

$table = $config->db['prefix'] . 'ideal_structure_part';
$tableAddon = $config->db['prefix'] . 'ideal_addon_page';

// Создание таблицы для страниц
$db->create($table, $cfg['fields']);

// Добавление первого раздела - главной страницы
$levels = $cfg['params']['levels'];
$digits = $cfg['params']['digits'];
$count = ($levels - 1) * $digits;

// Создаём главную страницу
$db->insert(
    $table,
    array(
        'ID' => 1,
        'prev_structure' => '0-1',
        'cid' => str_pad('1', $digits, '0', STR_PAD_LEFT) . str_repeat('0', $count),
        'lvl' => 1,
        'structure' => 'Ideal_Part',
        'template' => 'index.twig',
        'addon' => '[["1":"Ideal_Page", "Текст"]]',
        'name' => 'Главная',
        'url' => '/',
        'date_create' => time(),
        'date_mod' => time(),
        'is_active' => 1
    )
);

// Создаём текст для главной страницы
$db->insert(
    $tableAddon,
    array(
        'ID' => 1,
        'prev_structure' => '1-1',
        'tab_ID' => '1',
        'content' => '<p>Это Главная страница Вашего сайта.</p>',
    )
);
