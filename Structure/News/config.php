<?php

// Новости
return array(
    'params' => array(
        'in_structures' => array('Ideal_Part'), // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => '', // поле для входа в список потомков
        'field_sort' => 'date_create DESC', // поле, по которому проводится сортировка в CMS
        'field_list' => array('name', 'is_active', 'date_create')
    ),
    'fields' => array(
        'ID' => array(
            'label' => 'Идентификатор',
            'sql' => 'int(4) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden'
        ),
        'tag' => array(
            'label' => 'Теги',
            'sql' => '',
            'type' => 'Ideal_SelectMulti',
            'medium' => '\\Ideal\\Medium\\TagList\\Model'
        ),
        'name' => array(
            'label' => 'Заголовок',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text'
        ),
        'url' => array(
            'label' => 'URL',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_UrlAuto'
        ),
        'img' => array(
            'label' => 'Картинка',
            'sql' => 'varchar(255)',
            'type' => 'Ideal_Image'
        ),
        'annot' => array(
            'label' => 'Аннотация',
            'sql' => 'text',
            'type' => 'Ideal_Area'
        ),
        'date_create' => array(
            'label' => 'Дата создания',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet'
        ),
        'content' => array(
            'label' => 'Сообщение',
            'sql' => 'text',
            'type' => 'Ideal_RichEdit'
        ),
        'is_active' => array(
            'label' => 'Отображать на сайте',
            'sql' => 'bool',
            'type' => 'Ideal_Checkbox'
        ),
    ),
);
