<?php

// Страница
return [
    'params' => [
        'name' => 'PHP-файл',
    ],
    'fields' => [
        'ID' => [
            'label' => 'Идентификатор',
            'sql' => 'int(8) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden',
        ],
        'tab_ID' => [
            'label' => 'ID таба аддона',
            'sql' => 'int not null default 0',
            'type' => 'Ideal_Hidden',
        ],
        'php_file' => [
            'label' => 'Подключаемый файл',
            'sql' => 'varchar(255)',
            'type' => 'Ideal_Text',
        ],
        'content' => [
            'label' => 'Текст',
            'sql' => 'mediumtext',
            'type' => 'Ideal_RichEdit',
        ],
    ],
];
