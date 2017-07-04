<?php

// Новости
return array(
    'params' => array(
        'in_structures' => array(), // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => 'name', // поле для входа в список потомков
        'field_sort' => 'pos DESC', // поле, по которому проводится сортировка в CMS по умолчанию
        'field_list' => array('name')
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
        'structure' => array(
            'label' => 'Тип раздела',
            'sql' => 'varchar(30) not null',
            'type' => 'Ideal_Select',
            'medium' => '\\Ideal\\Medium\\StructureList\\Model'
        ),
        'pos' => array(
            'label' => 'Сортировка',
            'sql' => 'int(4) unsigned not null',
            'type' => 'Ideal_Text'
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
        'parent_url' => array(
            'label' => 'URL списка элементов',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text'
        ),
        'annot' => array(
            'label' => 'Аннотация',
            'sql' => 'text',
            'type' => 'Ideal_Area'
        ),
    ),
);
