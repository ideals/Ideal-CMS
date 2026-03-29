<?php

use Ideal\Medium\TemplateList\Model;

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
// Теги
return [
    'params' =>  [
        'in_structures' => ['Ideal_Part'], // в каких структурах можно создавать эту структуру
        'structures' => ['Ideal_Tag'],
        'elements_cms'  => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name'    => 'name', // поле для входа в список потомков
        'field_sort'    => 'cid ASC', // поле, по которому проводится сортировка в CMS
        'field_list'    => ['name', 'url', 'is_active', 'date_create'],
        'levels' => 6, // количество уровней вложенности
        'digits' => 3, // //кол-во разрядов
    ],
    'fields'   =>  [
        'ID' => [
            'label' => 'Идентификатор',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden',
        ],
        'template' => [
            'label' => 'Шаблон отображения',
            'sql' => "varchar(255) default 'index.twig'",
            'type' => 'Ideal_Template',
            'medium' => Model::class,
            'default'   => 'index.twig',
        ],
        'cid' => [
            'label' => '№',
            'sql' => 'char(' . (6 * 3) . ') not null',
            'type' => 'Ideal_Cid',
        ],
        'lvl' => [
            'label' => 'Уровень вложенности объекта',
            'sql' => 'int(1) unsigned not null',
            'type' => 'Ideal_Hidden',
        ],
        'structure' => [
            'label' => 'Тип раздела',
            'sql' => 'varchar(30) not null',
            'type' => 'Ideal_Select',
            'medium' => \Ideal\Medium\StructureList\Model::class,
        ],
        'name' => [
            'label' => 'Название',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text',
        ],
        'url' => [
            'label' => 'URL',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_UrlAuto',
        ],
        'date_create' => [
            'tab'   => 'SEO',
            'label' => 'Дата создания',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateSet',
        ],
        'date_mod' => [
            'tab'   => 'SEO',
            'label' => 'Дата модификации',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateAuto',
        ],
        'title' => [
            'tab'   => 'SEO',
            'label' => 'Title',
            'sql'   => 'text',
            'type'  => 'Ideal_Area',
        ],
        'keywords' => [
            'tab'   => 'SEO',
            'label' => 'Keywords tag',
            'sql'   => 'text',
            'type'  => 'Ideal_Area',
        ],
        'description' => [
            'tab'   => 'SEO',
            'label' => 'Description tag',
            'sql'   => 'text',
            'type'  => 'Ideal_Area',
        ],
        'is_active' => [
            'label' => 'Отображать на сайте',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox',
        ],
        'is_not_menu' => [
            'label' => 'Не выводить в меню',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox',
        ],
        'is_skip' => [
            'label' => 'Пропускать уровень',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox',
        ],
    ],
];
