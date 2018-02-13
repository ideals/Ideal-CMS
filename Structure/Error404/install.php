<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

// Инициализируем доступ к БД
$db = Ideal\Core\Db::getInstance();

$config = Ideal\Core\Config::getInstance();

$cfg = $config->getStructureByName('Ideal_Error404');

$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';

$_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
$max = $db->select($_sql);
$newPos = intval($max[0]['maxPos']) + 1;

// Создание таблицы для справочника
$db->create($config->db['prefix'] . 'ideal_structure_error404', $cfg['fields']);

$db->insert(
    $dataListTable,
    array(
        'prev_structure' => '0-3',
        'structure' => 'Ideal_Error404',
        'pos' => $newPos,
        'name' => 'Ошибки 404',
        'url' => 'oshibki-404',
        'parent_url' => '---',
        'annot' => ''
    )
);
