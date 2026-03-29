<?php

// ЯндексПоиск
return [
    'params' => [
        'name' => 'ЯндексПоиск',
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
        'elements_site' => [
            'label' => 'Количество элементов в выдаче',
            'sql' => 'int(8)',
            'type' => 'Ideal_Integer',
        ],
        'content' => [
            'label' => 'Текст',
            'sql' => 'mediumtext',
            'type' => 'Ideal_RichEdit',
        ],
    ],
];
