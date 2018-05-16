<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

// Контактное лицо
return array(
    'params' => array(),
    'fields' => array(
        'ID' => array(
            'label' => 'Идентификатор',
            'sql' => 'int(4) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden'
        ),
        'date_create' => array(
            'label' => 'Дата создания',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet'
        ),
        'name' => array(
            'label' => 'Имя',
            'sql' => 'varchar(255)',
            'type' => 'Ideal_Text'
        ),
        'email' => array(
            'label' => 'Электронный адрес',
            'sql' => 'text ',
            'type' => 'Ideal_Text'
        ),
        'client_id' => array(
            'label' => 'Client ID',
            'sql'   => 'text',
            'type'  => 'Ideal_Text'
        ),
        'phone' => array(
            'label' => 'Телефон',
            'sql' => 'text',
            'type' => 'Ideal_Text'
        ),
        'lead' => array(
            'label' => 'Лид',
            'sql' => 'int(8)',
            'type' => 'Ideal_Hidden',
        ),
    ),
);
