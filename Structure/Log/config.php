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
        'level' => array(
            'label' => 'Уровень лога',
            'sql' => 'varchar(9) not null',
        ),
        'user_id' => array(
            'label' => 'Идентификатор пользователя совершившего действие',
            'sql' => 'int(11) not null',
        ),
        'type' => array(
            'label' => 'Тип события',
            'sql' => 'varchar(100) not null',
        ),
        'message' => array(
            'label' => 'Событие',
            'sql' => 'varchar(255) not null',
        ),
        'json' => array(
            'label' => 'Дополнительные данные',
            'sql' => 'text',
        ),
    ),
);
