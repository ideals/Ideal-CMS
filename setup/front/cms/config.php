<?php

return [
    // Параметры подключения к БД
    'db' => [
        'host' => getenv('DB_HOST') ?: '[[DBHOST]]',
        'login' => getenv('DB_LOGIN') ?: '[[DBLOGIN]]',
        'password' => getenv('DB_PASSWORD') ?: '[[DBPASS]]',
        'name' => getenv('DB_NAME') ?: '[[DBNAME]]',
        'charset' => 'UTF-8',
        'prefix' => '[[DBPREFIX]]',
    ],
    'structures' => [
        // Подключаем структуру для страниц на сайте
        [
            'ID' => 1,
            'structure' => 'Ideal_Part',
            'name' => 'Страницы',
            'isShow' => 1,
            'hasTable' => true,
            'startName' => 'Главная',
            'url' => '',
        ],
        // Подключаем структуру для пользователей в админке
        [
            'ID' => 2,
            'structure' => 'Ideal_User',
            'name' => 'Пользователи',
            'isShow' => 1,
            'hasTable' => true,
        ],
        // Подключаем справочники
        [
            'ID' => 3,
            'structure' => 'Ideal_DataList',
            'name' => 'Справочники',
            'isShow' => 1,
            'hasTable' => true,
        ],
        // Подключаем сервисный модуль
        [
            'ID' => 4,
            'structure' => 'Ideal_Service',
            'name' => 'Сервис',
            'isShow' => 1,
            'hasTable' => false,
        ],
        // Подключаем структуру тегов
        [
            'ID' => 5,
            'structure' => 'Ideal_Tag',
            'name' => 'Теги',
            'isShow' => 0,
            'hasTable' => true,
        ],
        // Подключаем структуру новостей
        [
            'ID' => 6,
            'structure' => 'Ideal_News',
            'name' => 'Новости',
            'isShow' => 0,
            'hasTable' => true,
        ],
        // Подключаем структуру регистрации заказов
        [
            'ID' => 7,
            'structure' => 'Ideal_Order',
            'name' => 'Заказы с сайта',
            'isShow' => 0,
            'hasTable' => true,
        ],
        // Подключаем справочник 404-ых ошибок
        [
            'ID' => 8,
            'structure' => 'Ideal_Error404',
            'name' => 'Ошибки 404',
            'isShow' => 0,
            'hasTable' => true,
        ],
        // Подключаем структуру управления пользователями
        [
            'ID' => 9,
            'structure' => 'Ideal_Acl',
            'name' => 'Права пользователей',
            'isShow' => 0,
            'hasTable' => true,
        ],
        // Подключаем справочник групп пользователей
        [
            'ID' => 10,
            'structure' => 'Ideal_UserGroup',
            'name' => 'Группы пользователей',
            'isShow' => 0,
            'hasTable' => true,
        ],
        // Подключаем структуру ведения логов администраторов
        [
            'ID' => 11,
            'structure' => 'Ideal_Log',
            'name' => 'Лог администраторов',
            'isShow' => 0,
            'hasTable' => true,
        ],
    ],
];
