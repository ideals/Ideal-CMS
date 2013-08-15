<?php
// Страница
return array(
    'params' => array(
        'name' => 'PHP-файл',
    ),
    'fields' => array(
        'ID' => array(
            'label' => 'Идентификатор',
            'sql'   => 'int(8) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'structure_path' => array(
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden'
        ),
        'php_file' => array(
            'label' => 'Подключаемый файл',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'content' => array(
            'label' => 'Текст',
            'sql'   => 'mediumtext',
            'type'  => 'Ideal_RichEdit'
        ),
    )
);
