<?php
// @codingStandardsIgnoreFile
return array(
    'domain' => "[[SITENAME]]", // Домен | Ideal_Text
    'robotEmail' => "robot@[[SITENAME]]", // Почтовый ящик, с которого будут приходить письма с сайта | Ideal_Text
    'mailForm' => "info@[[SITENAME]]", // Почтовый ящик менеджера сайта | Ideal_Text
    'mailBcc' => "", // Дополнительная копия для писем с сайта | Ideal_Text
    'phone' => "(495) 123-45-67", // Телефон в шапке сайта | Ideal_Text
    'urlSuffix' => ".html", // Стандартный суффикс URL | Ideal_Text
    'allowResize' => "", // Разрешённые размеры изображений (по одному на строку) | Ideal_Area
    'cms' => array( // CMS
        'startUrl' => "[[SUBFOLDER_START_SLASH]]", // Начальная папка CMS | Ideal_Text
        'tmpFolder' => "/tmp", // Путь к папке с временными файлами | Ideal_Text
        'errorLog' => "firebug", // Способ уведомления об ошибках | Ideal_Select | {"firebug":"FireBug","email":"отправлять на email менеджера","display":"отображать в браузере","comment":"комментарий в html-коде","file":"сохранять в файл notice.log"}
        'adminEmail' => "[[CMSLOGIN]]", // Почта, на которую будут отправляться сообщения об ошибках | Ideal_Text
        'dirMode' => "0755", // Режим доступа к папке "0755" | Ideal_Text
        'fileMode' => "0644", // Режим доступа к файлу "0644" | Ideal_Text
        'error404Notice' => "0", // Уведомление о 404ых ошибках | Ideal_Checkbox
        'indexedOptions' => "", // Параметры, которые могут фигурировать в rel=canonical (по одному через запятую) | Ideal_Text
    ),
    'cache' => array( // Кэширование
        'jsAndCss' => "0", // Объединение и минификация css и js файлов | Ideal_Checkbox
        'templateSite' => "0", // Кэширование twig-шаблонов | Ideal_Checkbox
        'templateAdmin' => "0", // Кэширование twig-шаблонов админской части | Ideal_Checkbox
        'memcache' => "0", // Кэширование запросов к БД | Ideal_Checkbox
        'indexFile' => "index.html", // Индексный файл в папке | Ideal_Text
        'fileCache' => "0", // Кэширование страниц в файлы | Ideal_Checkbox
        'excludeFileCache' => "", // Адреса для исключения из кэша (по одному на строку, формат "регулярные выражения") | Ideal_RegexpList
        'browserCache' => "0", // Кэширование в браузере | Ideal_Checkbox
    ),
    'yandex' => array( // Яндекс
        'yandexLogin' => "", // Яндекс логин | Ideal_Text
        'yandexKey' => "", // Яндекс ключ | Ideal_Text
        'proxyUrl' => "", // Адрес прокси скрипта | Ideal_Text
        'loginHint' => "", // Электронный адрес или имя пользователя для доступа к сервису "Яндекс.Вебмастер" | Ideal_Text
        'clientId' => "", // Идентификатор приложения для доступа к сервису "Яндекс.Вебмастер" | Ideal_Text
        'token' => "", // Токен для авторизации в сервисе "Яндекс.Вебмастер" | Ideal_Text
    ),
    'smtp' => array( // SMTP
        'isFromParameter' => "1", // Дополнительное указание адреса получателя в sendmail | Ideal_Checkbox
        'isActive' => "", // Использовать этот SMTP при отправке | Ideal_Checkbox
        'server' => "", // Адрес SMTP-сервера | Ideal_Text
        'domain' => "", // Домен, с которого идёт отправка письма | Ideal_Text
        'port' => "", // Порт SMTP-сервера | Ideal_Text
        'user' => "", // Имя пользователя для авторизации на SMTP-сервере | Ideal_Text
        'password' => "", // Пароль для авторизации на SMTP-сервере | Ideal_Text
    ),
    'monitoring' => array( // Мониторинг
        'scanDir' => "", // Путь от корня системы до папки, в которой нужно проводить сканирование. Если пусто, то сканируется весь сайт | Ideal_Text
        'exclude' => "", // Регулярные выражения для исключения папок/файлов из сканирования | Ideal_RegexpList
    ),
);
