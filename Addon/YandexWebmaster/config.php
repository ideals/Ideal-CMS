<?php
// ЯндексВебмастер
return array(
    'params' => array(
        'name' => 'ЯндексВебмастер',
        'content_fields' => array('annot', 'addon_Ideal_Page_content')
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
        'yandexwebmastertext' => array(
            'label' => 'Текст для отправки в Яндекс.Вебмастер',
            'sql' => 'mediumtext',
            'type' => 'Ideal_YandexWebmasterText'
        ),
    )
);
