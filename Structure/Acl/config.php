<?php

/**
* Ideal CMS (http://idealcms.ru/)
*
* @link      http://github.com/ideals/idealcms репозиторий исходного кода
* @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
* @license   http://idealcms.ru/license.html LGPL v3
*/

// Права пользователя
return [
    'params' => [
        'in_structures' => [], // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => 'user_id', // поле для входа в список потомков
        'field_sort' => 'user_id DESC', // поле, по которому проводится сортировка в CMS по умолчанию
        'field_list' => ['user_id'],
    ],
    'fields' => [
        'user_group_id' => [
            'label' => 'Идентификатор группы пользователя',
            'sql' => 'int(11) NOT NULL',
        ],
        'structure' => [
            'label' => 'Обозначение определённого элемента структуры',
            'sql' => 'varchar(255) NOT NULL',
        ],
        'show' => [
            'label' => 'Показывать',
            'sql' => "bool DEFAULT '1' NOT NULL",
        ],
        'edit' => [
            'label' => 'Редактировать',
            'sql' => "bool DEFAULT '1' NOT NULL",
        ],
        'delete' => [
            'label' => 'Удалять',
            'sql' => "bool DEFAULT '1' NOT NULL",
        ],
        'enter' => [
            'label' => 'Входить',
            'sql' => "bool DEFAULT '1' NOT NULL",
        ],
    ],
];
