<?php

// Новости
return array(
    'params' => array(
        'in_structures' => array('Ideal_DataList'), // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => '', // поле для входа в список потомков
        'field_sort' => 'date_create DESC', // поле, по которому проводится сортировка в CMS
        'field_list' => array('date_create', 'name', 'email', 'price', 'referer', 'order_type')
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
        'date_create' => array(
            'label' => 'Дата создания',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet'
        ),
        'name' => array(
            'label' => 'Имя',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text'
        ),
        'email' => array(
            'label' => 'Email',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text'
        ),
        'price' => array(
            'label' => 'Сумма заказа',
            'sql'   => 'int',
            'type'  => 'Ideal_Price'
        ),
        'referer' => array(
            'label' => 'Источник перехода',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Referer'
        ),
        'content' => array(
            'tab'   => 'Заказ',
            'label' => 'Заказ',
            'sql'   => 'mediumtext',
            'type'  => 'Ideal_RichEdit'
        ),
        'order_type' => array(
            'label' => 'Тип заказа',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text'
        ),
        'customer' => array(
            'label' => 'Заказчик',
            'sql' => 'int(8)',
            'type' => 'Ideal_Select',
        ),
    ),
);
