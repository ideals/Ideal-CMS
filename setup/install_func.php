<?php

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш.
define('DOCUMENT_ROOT', getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT']);

// Абсолютный адрес размещения админки
define(
    'CMS_ROOT',
    $_SERVER['DOCUMENT_ROOT'] . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '/Ideal/setup'))
);

// Абсолютный адрес папки, в которой находится папка админки
define('ROOT', substr(CMS_ROOT, 0, strrpos(CMS_ROOT, '/')));


function checkPost($post)
{
    // Проверка наличия папки Ideal
    if (!is_dir(CMS_ROOT . '/Ideal')) {
        $errorText = "<strong>Ошибка</strong>. Скрипты системы должны быть установлены в папку {CMS_ROOT}/Ideal.";
        return $errorText;
    }

    // Проверка наличия папки кастомных скриптов CMS
    if (is_dir(CMS_ROOT . '/Ideal.c')) {
        $errorText = "<strong>Ошибка</strong>. При установке системы папка кастомных скриптов "
            . CMS_ROOT . "/Ideal.c не должна существовать.";
        return $errorText;
    }

    // Проверяем, есть ли права на запись в корневую папку сайта
    // TODO проверить, куда записывается .htaccess если CMS ставится не в корневую папку
    if (!is_writable(ROOT)) {
        $errorText = '<strong>Ошибка</strong>. Корневая папка сайта не доступна для записи.';
        return $errorText;
    }

    // Проверяем наличие в корне файла _.php
    if (file_exists(ROOT . '/_.php')) {
        $errorText = '<strong>Ошибка</strong>. В корне сайта уже есть файл <strong>_.php</strong> '
            . 'переименуйте или удалите его.';
        return $errorText;
    }

    // Проверяем наличие в корне файла .htaccess
    if (file_exists(ROOT . '/.htaccess')) {
        $errorText = '<strong>Ошибка</strong>. В корне сайта уже есть файл <strong>.htaccess</strong> '
            . 'переименуйте или удалите его.';
        return $errorText;
    }

    // Если это не вызов формы и нет ошибок с файлами, значит нет ошибок
    if (count($post) == 0) {
        return '';
    }

    global $fields;
    foreach ($fields as $v) {
        if ((!isset($post[$v]) || ($post[$v] == '')) && $v != 'subFolder') {
            $errorText = '<strong>Ошибка</strong>. Полe ' . $v . ' обязательно для заполнения.';
            return $errorText;
        }
    }

    // Сравниваем указанные пароли от админки
    if ($post['cmsPass'] !== $post['cmsPassRepeated']) {
        $errorText = '<strong>Ошибка</strong>. Пароль к админке не соответсвует повторно введённому паролю.';
        return $errorText;
    }

    // Проверяем возможность подключиться к БД
    $db = new mysqli();
    if ($db->connect($post['dbHost'], $post['dbLogin'], $post['dbPass']) === false) {
        $errorText = '<strong>Ошибка</strong>. Не могу подключиться к БД с параметрами: '
            . htmlspecialchars($post['dbHost']) . ', ' . htmlspecialchars($post['dbLogin']) . '.';
        return $errorText;
    }

    // Проверяем наличие заданной БД
    if ($db->select_db($post['dbName']) === false) {
        $errorText = '<strong>Ошибка</strong>. Не могу подключиться к базе: '
            . htmlspecialchars($post['dbName']) . '.';
        return $errorText;
    }

    // Проверяем наличие таблиц с заданным префиксом
    /** @var MySQLi_Result $result */
    $result = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($post['dbPrefix']) . "%'");
    if ($result->num_rows > 0) {
        $errorText = '<strong>Ошибка</strong>. В базе данных уже есть таблицы CMS '
            . 'с префиксом ' . htmlspecialchars($post['dbPrefix']);
        return $errorText;
    }

    return 'Ok';
}

function initFormValue($post, $fields)
{
    $values = array();
    foreach ($fields as $v) {
        $values[$v] = isset($post[$v]) ? htmlspecialchars($post[$v]) : '';
    }
    if ($values['dbPrefix'] == '') {
        $values['dbPrefix'] = 'i_';
    }
    if ($values['dbHost'] == '') {
        $values['dbHost'] = 'localhost';
    }
    return $values;
}

function installErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $error;
    if (in_array($errno, array(E_ERROR, E_WARNING, E_NOTICE))) {
        $error .= '<div class="alert">Ошибка [' . $errno . '] ' . $errstr
            . ', в строке ' . $errline . ' файла ' . $errfile . '</div>';
        return true;
    }
}

function fillPlaceholders($text)
{
    global $fields, $formValue;

    $search[] = '[[CMS]]';
    $replace[] = substr(CMS_ROOT, strrpos(CMS_ROOT, '/') + 1);

    foreach ($fields as $v) {
        $search[] = '[[' . strtoupper($v) . ']]';
        $replace[] = $formValue[$v];
    }

    if ($formValue['redirect'] == 1) {
        // 1 - редирект на без www
        $from = 'www.' . $formValue['siteName'];
        $to = $formValue['siteName'];
    } else {
        // 2 - редирект на www
        $from = $formValue['siteName'];
        $to = 'www.' . $formValue['siteName'];
    }

    $search[] = '[[DOMAIN_FROM_ESC]]';
    $replace[] = str_replace('.', '\.', $from);

    $search[] = '[[DOMAIN_FROM]]';
    $replace[] = $from;

    $search[] = '[[DOMAIN_TO]]';
    $replace[] = $to;

    $subFolder = substr(ROOT, strlen(DOCUMENT_ROOT) + 1);
    $search[] = '[[SUBFOLDER]]';
    $replace[] = $subFolder;

    $search[] = '[[SUBFOLDER_START_SLASH]]';
    $replace[] = ($subFolder == '') ? '' : '/' . $subFolder;

    $text = str_replace($search, $replace, $text);

    return $text;
}

function copyDir($src, $dst)
{
    $dir = opendir($src);
    if (!file_exists($dst)) {
        mkdir($dst);
    }
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDir($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

/**
 * ШАГ 1.
 * Копируем /Ideal/setup/front/_.php и /Ideal/setup/front/.htaccess в корень системы
 * Заменяя [[DOMAIN]] и [[DOMAIN_ESC]] на название домена
 * (для DOMAIN_ESC требуется экранирование точек с помощью слэша.
 */
function installCopyRoot()
{
    // Копируем файл _.php
    $file = file_get_contents('front/_.php');
    $file = fillPlaceholders($file);
    file_put_contents(ROOT . '/_.php', $file);

    // Копируем файл .htaccess
    $file = file_get_contents('front/.htaccess');
    $file = fillPlaceholders($file);
    file_put_contents(ROOT . '/.htaccess', $file);

    copyDir('front/files', ROOT . '/files');
    copyDir('front/images', ROOT . '/images');

    copyDir('front/css', ROOT . '/css');
    $file = file_get_contents('front/css/min.gen.php');
    $file = fillPlaceholders($file);
    file_put_contents(ROOT . '/css/min.gen.php', $file);

    copyDir('front/js', ROOT . '/js');
    $file = file_get_contents('front/js/min.gen.php');
    $file = fillPlaceholders($file);
    file_put_contents(ROOT . '/js/min.gen.php', $file);

    copyDir('../Library/bootstrap', ROOT . '/js/bootstrap');
    copyDir('../Library/jquery', ROOT . '/js/jquery');
    copyDir('../Library/fancybox', ROOT . '/js/fancybox');
    copyDir('../Library/jsFlashCookies', ROOT . '/js/jsFlashCookies');

    if (!file_exists(ROOT . '/tmp')) {
        mkdir(ROOT . '/tmp');
    }
    if (!file_exists(ROOT . '/tmp/templates')) {
        mkdir(ROOT . '/tmp/templates');
    }
    if (!file_exists(CMS_ROOT . '/Ideal.c')) {
        mkdir(CMS_ROOT . '/Ideal.c');
    }
}

/**
 * ШАГ 2.
 * Скопировать папку /Ideal/setup/front/cms/ в указанную папку для CMS
 *
 * @return bool
 */
function installCopyFront()
{
    if (!is_dir('front/cms')) {
        return false;
    }

    // Копируем папку с обязательными скриптами для фронтенда
    copyDir('front/cms', CMS_ROOT);

    return true;
}

/**
 * ШАГ 3.
 * Создать файл настроек в новой папке CMS
 */
function createConfig()
{
    // Прописываем в файле config.php конфигурационные данные
    $fileName = CMS_ROOT . '/config.php';
    $file = file_get_contents($fileName);
    $file = fillPlaceholders($file);
    file_put_contents($fileName, $file);

    // Прописываем в файле site-data.php конфигурационные данные
    $fileName = CMS_ROOT . '/site_data.php';
    $file = file_get_contents($fileName);
    $file = fillPlaceholders($file);
    file_put_contents($fileName, $file);

    // Прописываем в файле crontab конфигурационные данные
    $fileName = CMS_ROOT . '/crontab';
    $file = file_get_contents($fileName);
    $file = fillPlaceholders($file);
    file_put_contents($fileName, $file);
}

/**
 * ШАГ 4.
 * Создать в БД нужные таблицы
 */
function createTables()
{
    // В пути поиска по умолчанию включаем корень сайта, путь к Ideal и папке кастомизации CMS
    set_include_path(
        get_include_path()
        . PATH_SEPARATOR . CMS_ROOT
        . PATH_SEPARATOR . CMS_ROOT . '/Ideal.c/'
        . PATH_SEPARATOR . CMS_ROOT . '/Ideal/'
        . PATH_SEPARATOR . CMS_ROOT . '/Mods/'
    );

    // Подключаем автозагрузчик классов
    require_once 'Core/AutoLoader.php';

    $config = \Ideal\Core\Config::getInstance();

    // Каталог, в котором находятся модифицированные скрипты CMS
    $config->cmsFolder = CMS_ROOT;

    // Загружаем список структур из конфигурационных файлов структур
    $config->loadSettings();

    $db = \Ideal\Core\Db::getInstance();

    // Создаём таблицы аддонов
    if ($handle = opendir('../Addon')) {
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                if (is_dir('../Addon/' . $file)) {
                    $table = $config->db['prefix'] . 'ideal_addon_' . strtolower($file);
                    $fields = require('../Addon/' . $file . '/config.php');
                    $db->create($table, $fields['fields']);
                }
            }
        }
    }

    // Устанавливаем всё что нужно для работы структур
    foreach ($config->structures as $v) {
        list($module, $structure) = explode('_', $v['structure']);
        $module = ($module == 'Ideal') ? '' : $module . '/';
        if (stream_resolve_include_path($module . 'Structure/' . $structure . '/install.php') !== false) {
            require_once $module . 'Structure/' . $structure . '/install.php';
        }
    }

    // Создаём пользователя админки
    global $formValue;
    $db->insert(
        $config->db['prefix'] . 'ideal_structure_user',
        array(
            'email' => $formValue['cmsLogin'],
            'reg_date' => time(),
            'password' => crypt($formValue['cmsPass']),
            'is_active' => 1,
            'prev_structure' => '0-2'
        )
    );
}

/**
 * ШАГ 5.
 * Завершение установки - запись информации об этом в файл notice.log
 */
function installFinished()
{
    $fileName = CMS_ROOT . '/notice.log';

    // Записываем сообщение об успешной установке системы
    $text = date('d.m.y H:i') . " Установка системы произведена успешно.\r\n";
    file_put_contents($fileName, $text);

    // Присваиваем права 777
    chmod($fileName, 0777);
}
