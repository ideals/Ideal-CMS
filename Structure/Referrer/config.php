<?php

// Источники перехода
return array(
    'params' => array(
        'in_structures' => array('Ideal_DataList'),
        'structures' => array('Ideal_Referrer'),
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_sort' => 'cid', // поле, по которому проводится сортировка в CMS
        'field_list' => array('cid!40', 'ID', 'referrer', 'date_send'),
        'levels' => 1, // количество уровней вложенности
        'digits' => 3 // //кол-во разрядов
    ),
    'fields' => array(
        // label   - название поля в админке
        // sql     - описание поля для создания его в базе данных
        // type    - тип поля для вывода и обработки в админке
        // medium  - название класса, служащего посредником между данными БД и предоставлением их пользователю
        // default - значение по-умолчанию
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
        'referrer' => array(
            'label' => 'Источник перехода',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text'
        ),
        'date_send' => array(
            'label' => 'Дата отправки формы',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet'
        ),
    )
);
