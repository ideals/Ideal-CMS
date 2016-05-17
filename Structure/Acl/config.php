<?php

// Права пользователя
return array(
    'params' => array(
        'in_structures' => array(), // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => 'user_id', // поле для входа в список потомков
        'field_sort' => 'user_id DESC', // поле, по которому проводится сортировка в CMS
        'field_list' => array('user_id')
    ),
    'fields' => array(
        'user_id' => array(
            'label' => 'Идентификатор пользователя',
            'sql' => 'int(11) NOT NULL',
        ),
        'structure' => array(
            'label' => 'Обозначение определённого элемента структуры',
            'sql' => 'varchar(255) NOT NULL',
        ),
        'show' => array(
            'label' => 'Показывать',
            'sql' => "bool DEFAULT '1' NOT NULL",
        ),
        'edit' => array(
            'label' => 'Редактировать',
            'sql' => "bool DEFAULT '1' NOT NULL",
        ),
        'delete' => array(
            'label' => 'Удалять',
            'sql' => "bool DEFAULT '1' NOT NULL",
        ),
        'enter' => array(
            'label' => 'Входить',
            'sql' => "bool DEFAULT '1' NOT NULL",
        ),
    )
);
