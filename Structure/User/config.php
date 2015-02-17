<?php

// Таблица пользователей
return array(
    'params' => array(
        'structures' => array('Ideal_User'), // типы, которые можно создавать в этом разделе
        'elements_cms' => 20, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_sort' => 'reg_date DESC', // поле, по которому проводится сортировка в CMS
        'field_name' => '', // поле для входа в список потомков
        'field_list' => array('email', 'fio', 'reg_date', 'last_visit','count_login')
    ),
    'fields' => array(
        'ID' => array(
            'label' => 'ID',
            'sql' => 'int(8) unsigned NOT NULL auto_increment primary key',
            'type' => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden'
        ),
        'email' => array(
            'label' => 'E-mail',
            'sql' => 'varchar(128)',
            'type' => 'Ideal_Text'
        ),
        'password' => array(
            'label' => 'Пароль',
            'sql' => 'varchar(255) NOT NULL',
            'type' => 'Ideal_Password'
        ),
        'reg_date' => array(
            'label' => 'Дата регистрации',
            'sql' => "int(11) DEFAULT '0' NOT NULL",
            'type' => 'Ideal_DateSet'
        ),
        'last_visit' => array(
            'label' => 'Последний вход',
            'sql' => "int(11) DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Date'
        ),
        'act_key' => array(
            'label' => 'Ключ активации',
            'sql' => 'varchar(32)',
            'type' => 'Ideal_Hidden'
        ),
        'new_password' => array(
            'label' => 'Новый пароль',
            'sql' => 'varchar(32)',
            'type' => 'Ideal_Hidden'
        ),
        'fio' => array(
            'label' => 'ФИО',
            'sql' => 'varchar(250)',
            'type' => 'Ideal_Text'
        ),
        'phone' => array(
            'label' => 'Телефон',
            'sql' => 'varchar(250)',
            'type' => 'Ideal_Text'
        ),
        'is_active' => array(
            'label' => 'Активирован',
            'sql' => "bool not null default '0'",
            'type' => 'Ideal_Checkbox'
        ),
        'count_login' => array(
            'label' => 'Число попыток входа',
            'sql'   => "int(11) DEFAULT '0'",
            'type'  => 'Ideal_Hidden'
        ),
    ),
);