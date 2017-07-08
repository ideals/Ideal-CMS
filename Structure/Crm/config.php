<?php

// Заказчики
return array(
    'params' => array(
        'in_structures' => array('Ideal_DataList'), // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => '', // поле для входа в список потомков
        'field_sort' => 'date_create DESC', // поле, по которому проводится сортировка в CMS
        'field_list' => array('ID', 'date_create', 'name')
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
            'sql' => 'varchar(255)',
            'type' => 'Ideal_Text'
        ),
        'emails' => array(
            'label' => 'Электронные адреса',
            'sql' => 'text ',
            'type' => 'Ideal_JsonArea'
        ),
        'client_ids' => array(
            'label' => 'Client ID',
            'sql'   => 'text',
            'type'  => 'Ideal_JsonArea'
        ),
        'phones' => array(
            'label' => 'Телефоны',
            'sql' => 'text',
            'type' => 'Ideal_JsonArea'
        ),
        'join_with_customer' => array(
            'label' => 'Объединить с заказчиком',
            'sql' => '',
            'type' => 'Ideal_Select',
            'medium' => '\\Ideal\\Medium\\CustomerList\\Model'
        ),
    ),
);
