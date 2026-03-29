<?php

// Страница
return [
    'params' => [
        'name' => 'Карта сайта',
    ],
    'fields' => [
        'ID' => [
            'label' => 'ID',
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
        'level' => [
            'label' => 'Кол-во отображаемых уровней',
            'sql' => 'int not null default 0',
            'type' => 'Ideal_Text',
        ],
        'disallow' => [
            'label' => 'Регулярные выражения для отсеивания URL',
            'sql' => 'text',
            'type' => 'Ideal_Area',
            'help' => 'Регулярные выражения записываются по одному на каждую строку и обязательно '
                . 'с открывающими и закрывающими слэшами',
        ],
        'content' => [
            'label' => 'Текст',
            'sql' => 'mediumtext',
            'type' => 'Ideal_RichEdit',
        ],
    ],
];
