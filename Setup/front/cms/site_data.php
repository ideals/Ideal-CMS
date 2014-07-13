<?php
return array(
    'domain' => '[[SITENAME]]',
    // Домен | Ideal_Text
    'robotEmail' => 'robot@[[SITENAME]]',
    // Почтовый ящик, с которого будут приходить письма с сайта | Ideal_Text
    'mailForm' => 'info@[[SITENAME]]',
    // Почтовый ящик менеджера сайта | Ideal_Text
    'phone' => '(495) 123-45-67',
    // Телефон в шапке сайта | Ideal_Text
    'startUrl' => '[[SUBFOLDER_START_SLASH]]',
    // Начальная папка CMS | Ideal_Text
    'urlSuffix' => '.html',
    // Стандартный суффикс URL | Ideal_Text
    'tmpDir' => '/tmp',
    // Путь к папке с временными файлами | Ideal_Text
    'errorLog' => 'firebug',
    // Способ уведомления об ошибках | Ideal_Select | {"firebug":"FireBug","email":"отправлять на email менеджера","display":"отображать в браузере","comment":"комментарий в html-коде","file":"сохранять в файл notice.log"}
    'allowResize' => '',
    // Разрешённые размеры изображений (по одному на строку) | Ideal_Area
    'templateCachePath' => '/tmp/templates',
    // Путь к папке с кэшем twig-шаблонов | Ideal_Text
    'isTemplateCache' => '0',
    // Кэширование twig-шаблонов | Ideal_Checkbox
    'isTemplateAdminCache' => '0',
    // Кэширование twig-шаблонов админской части | Ideal_Checkbox
);