<?php
// Страница
return array(
    'params' => array(
        'name' => 'Карта сайта',
    ),
    'fields' => array(
        'ID' => array(
            'label' => 'ID',
            'sql'   => 'int(8) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'structure_path' => array(
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden'
        ),
        'level' => array(
            'label' => 'Кол-во отображаемых уровней',
            'sql'   => 'int default 0',
            'type'  => 'Ideal_Text'
        ),
        'content' => array(
            'label' => 'Текст',
            'sql'   => 'mediumtext',
            'type'  => 'Ideal_RichEdit'
        ),
    )
);
