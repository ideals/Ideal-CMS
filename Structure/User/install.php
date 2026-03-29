<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
// Инициализируем доступ к БД
use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Util;

$db = Db::getInstance();
$config = Config::getInstance();

$cfg = $config->getStructureByName('Ideal_User');

if (is_bool($cfg)) {
    Util::addError('Не найдена структура Ideal_User');
    return;
}

// Создание таблицы для страниц
$db->create($config->db['prefix'] . 'ideal_structure_user', $cfg['fields']);
