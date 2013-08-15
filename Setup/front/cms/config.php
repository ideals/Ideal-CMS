<?php

return array(
    // Параметры подключения к БД
    'db' => array(
        'host'     => getenv('DB_HOST') ? getenv('DB_HOST') : '[[DBHOST]]',
        'login'    => '[[DBLOGIN]]',
        'password' => '[[DBPASS]]',
        'name'     => '[[DBNAME]]',
        'charset'  => '[[DBCHARSET]]', // WINDOWS-1251 или UTF-8
        'prefix'   => '[[DBPREFIX]]'
    ),

    'structures' => array(
        // Подключаем структуру для страниц на сайте
        array(
            'ID'        => 1,
            'structure' => 'Ideal_Part',
            'name'      => 'Страницы',
            'isShow'    => 1,
            'hasTable'  => true,
            'startName' => 'Главная',
            'url'       => ''
        ),
        // Подключаем структуру для пользователей в админке
        array(
            'ID'        => 2,
            'structure' => 'Ideal_User',
            'name'      => 'Пользователи',
            'isShow'    => 1,
            'hasTable'  => true
        ),
        // Подключаем справочники
        array(
            'ID'        => 3,
            'structure' => 'Ideal_DataList',
            'name'      => 'Справочники',
            'isShow'    => 0,
            'hasTable'  => true
        ),
        // Подключаем сервисный модуль
        array(
            'ID'        => 4,
            'structure' => 'Ideal_Service',
            'name'      => 'Сервис',
            'isShow'    => 1,
            'hasTable'  => false,
            'items'     => array(
                1 => array(
                    'ID'     => 1,
                    'name'   => 'Настройки',
                    'info'   => '',
                    'action' => 'Config'
                ),
                2 => array(
                    'ID'     => 2,
                    'name'   => 'Проверка целостности базы данных',
                    'info'   => '',
                    'action' => 'CheckDb'
                )
            )
        )
    )
);
