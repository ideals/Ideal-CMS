<?php
// Страница
return array(
    'params' => array(
        'name' => 'Текст',
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
        'content' => array(
            'label' => 'Текст',
            'sql' => 'mediumtext',
            'type' => 'Ideal_RichEdit'
        )
    )
);
