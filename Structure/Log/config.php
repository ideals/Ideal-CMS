<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

// Журнал действий
return array(
    'params' => array(
    ),
    'fields' => array(
        'ID' => array(
            'label' => 'Идентификатор',
            'sql' => 'int(4) unsigned not null auto_increment primary key',
        ),
        'date_create' => array(
            'label' => 'Дата создания записи',
            'sql' => 'int(11) not null',
        ),
        'user' => array(
            'label' => 'Идентификатор пользователя совершившего действие',
            'sql' => 'int(11) not null',
        ),
        'structure' => array(
            'label' => 'Идентификатор структуры над которой совершилось действие',
            'sql' => 'int(11) not null',
        ),
        'element' => array(
            'label' => 'Обозначение элемента над которым совершилось действие',
            'sql' => 'varchar(255) not null',
        ),
        'action' => array(
            'label' => 'Какое именно действие было совершено',
            'sql' => 'varchar(255) not null',
        ),
    ),
);
