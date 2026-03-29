<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

// Журнал действий
return [
    'params' => [
        'in_structures' => ['Ideal_DataList'], // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'field_name' => '', // поле для входа в список потомков
        'field_sort' => 'date_create DESC', // поле, по которому проводится сортировка в CMS
        'field_list' => ['date_create', 'type', 'message', 'user_id'],
    ],
    'fields' => [
        'ID' => [
            'label' => 'Идентификатор',
            'sql' => 'int(4) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden',
        ],
        'date_create' => [
            'label' => 'Дата создания записи',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet',
        ],
        'level' => [
            'label' => 'Уровень лога',
            'sql' => 'varchar(9) not null',
            'type' => 'Ideal_Text',
        ],
        'user_id' => [
            'label' => 'Пользователь совершивший действие',
            'sql' => 'int(8) not null',
            'type' => 'Ideal_Select',
            'medium' => '\\Ideal\\Medium\\UserList\\Model',
        ],
        'type' => [
            'label' => 'Тип события',
            'sql' => 'varchar(100) not null',
            'type' => 'Ideal_Text',
        ],
        'message' => [
            'label' => 'Событие',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text',
        ],
        'json' => [
            'label' => 'Дополнительные данные',
            'sql' => 'text',
            'type' => 'Ideal_Text',
        ],
    ],
];
