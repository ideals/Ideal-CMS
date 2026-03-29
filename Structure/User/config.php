<?php

use Ideal\Medium\UserGroupList\Model;

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
// Таблица пользователей
return [
    'params' => [
        'structures' => ['Ideal_User'], // типы, которые можно создавать в этом разделе
        'elements_cms' => 20, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_sort' => 'reg_date DESC', // поле, по которому проводится сортировка в CMS по умолчанию
        'field_name' => '', // поле для входа в список потомков
        'field_list' => ['email', 'fio', 'reg_date', 'last_visit'],
    ],
    'fields' => [
        'ID' => [
            'label' => 'ID',
            'sql' => 'int(8) unsigned NOT NULL auto_increment primary key',
            'type' => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden',
        ],
        'email' => [
            'label' => 'E-mail',
            'sql' => 'varchar(128) NOT NULL',
            'type' => 'Ideal_Text',
        ],
        'password' => [
            'label' => 'Пароль',
            'sql' => 'varchar(255) NOT NULL',
            'type' => 'Ideal_Password',
        ],
        'user_group' => [
            'label' => 'Группа пользователя',
            'sql' => 'int(8)',
            'type' => 'Ideal_Select',
            'medium' => Model::class,
        ],
        'reg_date' => [
            'label' => 'Дата регистрации',
            'sql' => "int(11) DEFAULT '0' NOT NULL",
            'type' => 'Ideal_DateSet',
        ],
        'last_visit' => [
            'label' => 'Последний вход',
            'sql' => "int(11) DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Date',
            'default' => '0',
        ],
        'act_key' => [
            'label' => 'Ключ активации',
            'sql' => 'varchar(32)',
            'type' => 'Ideal_Hidden',
        ],
        'new_password' => [
            'label' => 'Новый пароль',
            'sql' => 'varchar(32)',
            'type' => 'Ideal_Hidden',
        ],
        'fio' => [
            'label' => 'ФИО',
            'sql' => 'varchar(250)',
            'type' => 'Ideal_Text',
        ],
        'phone' => [
            'label' => 'Телефон',
            'sql' => 'varchar(250)',
            'type' => 'Ideal_Text',
        ],
        'is_active' => [
            'label' => 'Активирован',
            'sql' => "bool not null default '0'",
            'type' => 'Ideal_Checkbox',
        ],
        'counter_failures' => [
            'label' => 'Счётчик неудачных попыток авторизации',
            'sql' => "int(11) DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Hidden',
            'default' => '0',
        ],
    ],
];
