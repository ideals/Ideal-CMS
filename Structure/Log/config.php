<?php

// Справочник "Логи"
return array(
    'params' => array(
        'in_structures' => array('Ideal_DataList'), // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => '', // поле для входа в список потомков
        'field_sort' => 'date_create DESC', // поле, по которому проводится сортировка в CMS
        'field_list' => array('date_create', 'user_id', 'event_type', 'what_happened')
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
            'label' => 'Дата события',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet'
        ),
        'user_id' => array(
            'label' => 'Идентификатор пользователя',
            'sql' => 'int not null',
            'type' => 'Ideal_Integer'
        ),
        'event_type' => array(
            'label' => 'Тип события',
            'sql' => 'text not null',
            'type' => 'Ideal_Text'
        ),
        'what_happened' => array(
            'label' => 'Cуть события',
            'sql' => 'text not null',
            'type' => 'Ideal_Text'
        ),
    ),
);
