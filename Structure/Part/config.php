<?php

use Ideal\Medium\TemplateList\Model;

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
// Страницы сайта
return [
    'params' => [
        'in_structures' => ['Ideal_Part'],
        'structures' => ['Ideal_Part', 'Ideal_News'], // типы, которые можно создавать в этом разделе
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => 'name', // поля для вывода информации по объекту
        'field_sort' => 'cid', // поле, по которому проводится сортировка в CMS по умолчанию
        'field_list' => ['cid!40', 'ID', 'name', 'date_mod', 'url'],
        'levels' => 6, // количество уровней вложенности
        'digits' => 3, // //кол-во разрядов
    ],
    'fields' => [
        // label   - название поля в админке
        // sql     - описание поля для создания его в базе данных
        // type    - тип поля для вывода и обработки в админке
        // class   - название класса, служащего посредником между данными БД и предоставлением их пользователю
        // default - значение по-умолчанию (пока не реализовано)
        'ID' => [
            'label' => 'Идентификатор',
            'sql' => 'int(8) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden',
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
        'addon' => [
            'label' => 'Аддоны',
            'sql' => "varchar(255) not null default '[[\"1\",\"Ideal_Page\",\"Текст\"]]'",
            'type' => 'Ideal_Addon',
            'medium'    => \Ideal\Medium\AddonList\Model::class,
            'available' =>  ['Ideal_Page', 'Ideal_PhpFile', 'Ideal_Photo', 'Ideal_SiteMap',
                'Ideal_YandexSearch'],
            'default'   => '[["1","Ideal_Page","Текст"]]',
        ],
        'name' => [
            'label' => 'Название',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text',
        ],
        'url' => [
            'label' => 'URL',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_UrlAuto',
        ],
        'annot' => [
            'label' => 'Описание',
            'sql' => 'text',
            'type' => 'Ideal_Area',
        ],
        'img' => [
            'label' => 'Картинка',
            'sql' => 'varchar(255)',
            'type' => 'Ideal_Image',
        ],
        'date_create' => [
            'label' => 'Дата создания',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet',
        ],
        'date_mod' => [
            'label' => 'Дата модификации',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateAuto',
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
        'is_self_menu' => [
            'label' => 'Не выводить своё подменю',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox',
        ],
        'is_skip' => [
            'label' => 'Пропускать уровень',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox',
        ],
        'title' => [
            'tab' => 'SEO',
            'label' => 'Title',
            'sql' => 'text',
            'type' => 'Ideal_Area',
        ],
        'keywords' => [
            'tab' => 'SEO',
            'label' => 'Keywords tag',
            'sql' => 'text',
            'type' => 'Ideal_Area',
        ],
        'description' => [
            'tab' => 'SEO',
            'label' => 'Description tag',
            'sql' => 'text',
            'type' => 'Ideal_Area',
        ],
    ],
];
