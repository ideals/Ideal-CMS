<?php

// Страницы сайта
return array(
    'params' => array(
        'in_structures' => array('Ideal_Part'),
        'structures' => array('Ideal_Part', 'Ideal_News'), // типы, которые можно создавать в этом разделе
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => 'name', // поля для вывода информации по объекту
        'field_sort' => 'cid', // поле, по которому проводится сортировка в CMS
        'field_list' => array('cid!40', 'ID', 'name', 'date_mod', 'url'),
        'levels' => 6, // количество уровней вложенности
        'digits' => 3 // //кол-во разрядов
    ),
    'fields' => array(
        // label   - название поля в админке
        // sql     - описание поля для создания его в базе данных
        // type    - тип поля для вывода и обработки в админке
        // class   - название класса, служащего посредником между данными БД и предоставлением их пользователю
        // default - значение по-умолчанию (пока не реализовано)
        'ID' => array(
            'label' => 'Идентификатор',
            'sql' => 'int(8) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden'
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
        'addon' => array(
            'label' => 'Аддоны',
            'sql' => "varchar(255) not null default '[[\"1\",\"Ideal_Page\",\"Текст\"]]'",
            'type' => 'Ideal_Addon',
            'medium'    => '\\Ideal\\Medium\\AddonList\\Model',
            'available' =>  array('Ideal_Page', 'Ideal_PhpFile', 'Ideal_Photo', 'Ideal_SiteMap', 'Ideal_YandexSearch'),
            'default'   => '[["1","Ideal_Page","Текст"]]',
        ),
        'name' => array(
            'label' => 'Название',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text'
        ),
        'url' => array(
            'label' => 'URL',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_UrlAuto'
        ),
        'annot' => array(
            'label' => 'Описание',
            'sql' => 'text',
            'type' => 'Ideal_Area'
        ),
        'img' => array(
            'label' => 'Картинка',
            'sql' => 'varchar(255)',
            'type' => 'Ideal_Image'
        ),
        'date_create' => array(
            'label' => 'Дата создания',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet'
        ),
        'date_mod' => array(
            'label' => 'Дата модификации',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateAuto'
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
        'is_self_menu' => array(
            'label' => 'Не выводить своё подменю',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox'
        ),
        'is_skip' => array(
            'label' => 'Пропускать уровень',
            'sql' => "bool DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Checkbox'
        ),
        'title' => array(
            'tab' => 'SEO',
            'label' => 'Title',
            'sql' => 'text',
            'type' => 'Ideal_Area'
        ),
        'keywords' => array(
            'tab' => 'SEO',
            'label' => 'Keywords tag',
            'sql' => 'text',
            'type' => 'Ideal_Area'
        ),
        'description' => array(
            'tab' => 'SEO',
            'label' => 'Description tag',
            'sql' => 'text',
            'type' => 'Ideal_Area'
        ),
    )
);
