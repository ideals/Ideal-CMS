<?php
// Контактное лицо
return array(
    'params' => array(
        'name' => 'Контактное лицо',
    ),
    'fields' => array(
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
        'tab_ID' => array(
            'label' => 'ID таба аддона',
            'sql' => 'int not null default 0',
            'type' => 'Ideal_Hidden'
        ),
        'contact_person' => array(
            'label' => 'Данные контактного лица',
            'type' => 'Ideal_ContactPerson',
            'sql' => 'int not null',
        ),
    )
);
