<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

// Лид
return array(
    'params' => array(
        'field_sort' => 'ID',
        'field_list' => array('ID', 'cpName', 'lastInteraction', 'cpPhone', 'cpEmail'),
        'field_name' => 'cpName',
        'elements_cms' => PHP_INT_MAX
    ),
    'fields' => array(
        'ID' => array(
            'label' => 'ID',
            'sql' => 'int(8) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden'
        ),
        'name' => array(
            'label' => 'Название',
            'sql' => 'varchar(255)',
            'type' => 'Ideal_Text'
        ),
        'comment' => array(
            'label' => 'Комментарий',
            'sql' => 'text',
            'type' => 'Ideal_Area'
        ),
        'addon' => array(
            'label' => 'Аддоны',
            'sql' => "varchar(255) not null default '[[\"1\",\"Ideal_ContactPerson\",\"Контактное лицо\"]]'",
            'type' => 'Ideal_Addon',
            'medium'    => '\\Ideal\\Medium\\AddonList\\Model',
            'available' =>  array('Ideal_ContactPerson'),
            'default'   => '[["1","Ideal_ContactPerson","Контактное лицо"]]',
        ),
        'cpName' => array(
            'label' => 'Список имён контактных лиц лида',
            'type' => 'Ideal_CpName'
        ),
        'lastInteraction' => array(
            'label' => 'Дата последнего взаимодействия',
            'type' => 'Ideal_LastInteraction'
        ),
        'cpPhone' => array(
            'label' => 'Телефон',
            'type' => 'Ideal_CpPhone'
        ),
        'cpEmail' => array(
            'label' => 'Почта',
            'type' => 'Ideal_CpEmail'
        ),
    ),
);
