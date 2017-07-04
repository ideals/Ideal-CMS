<?php

// Справочник "Группа пользователей"
return array(
    'params' => array(
        'in_structures' => array('Ideal_DataList'), // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => '', // поле для входа в список потомков
        'field_sort' => 'name', // поле, по которому проводится сортировка в CMS по умолчанию
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
        'name' => array(
            'label' => 'Название',
            'sql' => 'text not null',
            'type' => 'Ideal_Text'
        ),
    ),
);
