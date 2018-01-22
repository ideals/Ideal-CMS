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

$cfg = $config->getStructureByName('Ideal_DataList');

// Создание таблицы для справочника
$db->create($config->db['prefix'] . 'ideal_structure_datalist', $cfg['fields']);
