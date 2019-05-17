<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

// Теги
return array(
    'params' => array (
        'in_structures' => array('Ideal_Part'), // в каких структурах можно создавать эту структуру
        'structures' => array('Ideal_Tag'),
        'elements_cms'  => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name'    => 'name', // поле для входа в список потомков
        'field_sort'    => 'cid ASC', // поле, по которому проводится сортировка в CMS
        'field_list'    => array('name', 'url', 'is_active', 'date_create'),
        'levels' => 6, // количество уровней вложенности
        'digits' => 3 // //кол-во разрядов
     ),
    'fields'   => array (
        'ID' => array(
            'label' => 'Идентификатор',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden'
        ),
        'template' => array(
            'label' => 'Шаблон отображения',
            'sql' => "varchar(255) default 'index.twig'",
            'type' => 'Ideal_Template',
            'medium' => '\\Ideal\\Medium\\TemplateList\\Model',
            'default'   => 'index.twig',
        ),
        'cid' => array(
            'label' => '№',
            'sql' => 'char(' . (6 * 3) . ') not null',
            'type' => 'Ideal_Cid'
        ),
        'lvl' => array(
            'label' => 'Уровень вложенности объекта',
            'sql' => 'int(1) unsigned not null',
            'type' => 'Ideal_Hidden'
        ),
        'structure' => array(
            'label' => 'Тип раздела',
            'sql' => 'varchar(30) not null',
            'type' => 'Ideal_Select',
            'medium' => '\\Ideal\\Medium\\StructureList\\Model'
        ),
        'name' => array(
            'label' => 'Название',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text'
        ),
        'url' => array(
            'label' => 'URL',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_UrlAuto'
        ),
        'date_create' => array(
            'tab'   => 'SEO',
            'label' => 'Дата создания',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateSet'
        ),
        'date_mod' => array(
            'tab'   => 'SEO',
            'label' => 'Дата модификации',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateAuto'
        ),
        'title' => array(
            'tab'   => 'SEO',
            'label' => 'Title',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'keywords' => array(
            'tab'   => 'SEO',
            'label' => 'Keywords tag',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'description' => array(
            'tab'   => 'SEO',
            'label' => 'Description tag',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'is_active' => array(
            'label' => 'Отображать на сайте',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox'
        ),
        'is_not_menu' => array(
            'label' => 'Не выводить в меню',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox'
        ),
        'is_skip' => array(
            'label' => 'Пропускать уровень',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox'
        ),
    ),
);
