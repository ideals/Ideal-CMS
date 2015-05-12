<?php
// ЯндексПоиск
return array(
    'params' => array(
        'name' => 'ЯндексПоиск',
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
        'yandexLogin' => array(
            'label' => 'Яндекс логин',
            'sql' => 'varchar(255)',
            'type' => 'Ideal_Text'
        ),
        'yandexKey' => array(
            'label' => 'Яндекс ключ',
            'sql' => 'varchar(255)',
            'type' => 'Ideal_Text'
        ),
        'elements_site' => array(
            'label' => 'Количество элементов в выдаче',
            'sql' => 'int(8)',
            'type' => 'Ideal_Integer'
        ),
        'content' => array(
            'label' => 'Текст',
            'sql' => 'mediumtext',
            'type' => 'Ideal_RichEdit'
        ),
    )
);
