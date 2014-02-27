<?php
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING); //| E_STRICT

ini_set('display_errors', 'On');

if (isset($_POST['checkMysql'])) {
    ini_set('display_errors', 'Off');
    $answer = checkMysql();
    print $answer;
    exit; //ajax запрос, дальнейшее выполнение кода не требуеться
}

set_error_handler('installErrorHandler');

//Проверяем правильность Url
//Если Url неправильный (путь к скрипту содержит символы в неправильном регистре), делаем редирект с указанием правильного пути
$scriptDir = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']);
if ($scriptDir !== $_SERVER['REQUEST_URI']) {
    header("Location: $scriptDir");
}


define('CMS_ROOT', $_SERVER['DOCUMENT_ROOT']
    . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '/Ideal/Setup')));

define('ROOT', substr(CMS_ROOT, 0, strrpos(CMS_ROOT, '/')));

$fields = array(
    'siteName',
    'redirect',
    'sitePath',
    'cmsLogin',
    'cmsPass',
    'cmsPassRepeated',
    'dbHost',
    'dbLogin',
    'dbPass',
    'dbName',
    'dbCharset',
    'dbPrefix'
);

$formValue = initFormValue($_POST, $fields);
$errorText = checkPost($_POST);

if ($errorText == 'Ok') {
    installCopyRoot();
    installCopyFront();
    createConfig();
    createTables();
    installFinished();
    header('Location: ../../index.php');
    exit;
}

@ header('Content-Type: text/html; charset=utf-8');
require_once('index.html'); // Подключение шаблона диалога установки CMS


function checkMysql()
{
    $answer = array('error' => false, 'text' => '');
    $server = $_POST['dbHost'];
    $user = $_POST['dbLogin'];
    $password = $_POST['dbPass'];
    $database = $_POST['dbName'];
    $prefix = $_POST['dbPrefix'];
    $tablesClose = array(
        'ideal_structure_datalist' => true,
        'ideal_structure_news' => true,
        'ideal_structure_part' => true,
        'ideal_structure_user' => true,
        'ideal_template_page' => true,
        'ideal_template_phpfile' => true,
        'ideal_template_sitemap' => true,
    );
    foreach ($tablesClose as $k => $v) {
        unset($tablesClose[$k]);
        $tablesClose[$prefix . $k] = $v;
    }

    $dbLink = mysql_connect($server, $user, $password);

    if ($dbLink) {
        $answer['text'] = "Соединение установлено.\n";

        $selected = mysql_select_db($database, $dbLink);
        if ($selected) {
            $answer['text'] .= "Подключение к базе данных прошло успешно.\n";
            $tables = mysql_list_tables($database, $dbLink);
            while ($row = mysql_fetch_row($tables)) {
                if (isset($tablesClose[$row[0]]) && $tablesClose[$row[0]]) {
                    $answer['error'] = true;
                    $answer['text'] = 'База не пуста! Таблицы с данным префиксом уже существуют в базе.';
                    break;
                }
            }
        } else {
            $answer['error'] = true;
            $answer['text'] .= 'База данных не найдена или отсутствует доступ.';
        }
    } else {
        $answer['error'] = true;
        $answer['text'] = 'Ошибка подключения к серверу баз данных.';
    }
    return json_encode($answer);
}

function checkPost($post)
{
    // Проверка наличия папки Ideal
    if (!is_dir(CMS_ROOT . '/Ideal')) {
        $errorText = "<strong>Ошибка</strong>. Скрипты системы должны быть установлены в папку {CMS_ROOT}/Ideal.";
        return $errorText;
    }

    // Проверка наличия папки кастомных скриптов CMS
    if (is_dir(CMS_ROOT . '/Ideal.c')) {
        $errorText = "<strong>Ошибка</strong>. При установке системы папка кастомных скриптов " . CMS_ROOT . "/Ideal.c не должна существовать.";
        return $errorText;
    }

    // Проверяем, есть ли права на запись в корневую папку сайта
    // TODO проверить, куда пишутся .htaccess если CMS ставится не в корневую папку
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
        if ((!isset($post[$v]) OR ($post[$v] == '')) AND $v != 'subFolder') {
            $errorText = '<strong>Ошибка</strong>. Полe ' . $v . ' обязательно для заполнения.';
            return $errorText;
        }
    }

    //Сравниваем указанные пароли от админки
    if ($post['cmsPass'] !== $post['cmsPassRepeated']) {
        $errorText = '<strong>Ошибка</strong>. Пароль к админке не соответсвует повторно введённому паролю.';
        return $errorText;
    }

    // Проверяем возможность подключиться к БД
    if (mysql_connect($post['dbHost'], $post['dbLogin'], $post['dbPass']) === false) {
        $errorText = '<strong>Ошибка</strong>. Не могу подключиться к БД с параметрами: '
            . htmlspecialchars($post['dbHost']) . ', ' . htmlspecialchars($post['dbLogin']) . '.';
        return $errorText;
    }

    // Проверяем наличие заданной БД
    if (mysql_select_db($post['dbName']) === false) {
        $errorText = '<strong>Ошибка</strong>. Не могу подключиться к базе: '
            . htmlspecialchars($post['dbName']) . '.';
        return $errorText;
    }

    // Проверяем наличие таблиц с заданным префиксом
    $result = mysql_query("SHOW TABLES LIKE '" . mysql_real_escape_string($post['dbPrefix']) . "%'");
    if (mysql_numrows($result) > 0) {
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
    if ($values['sitePath'] == '') {
        $values['sitePath'] = ROOT;
    }
    if ($values['dbCharset'] != '') {
        $charsets = array(0 => 'UTF-8', 1 => 'WINDOWS-1251');
        $values['dbCharset'] = $charsets[$values['dbCharset']];
    }
    if ($values['siteName'] == '') {
        $values['siteName'] = $_SERVER['SERVER_NAME'];
    }
    return $values;
}


function installErrorHandler($errno, $errstr, $errfile, $errline)
{
    $_err = 'Ошибка [' . $errno . '] ' . $errstr . ', в строке ' . $errline . ' файла ' . $errfile;
    print $_err;
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

    $search[] = '[[DOMAIN_ESC]]';
    if(substr($formValue['siteName'],0,4) == 'www.'){
        $formValue['siteName'] = substr($formValue['siteName'], 4);
    }
    // 1 - редирект без www
    // 2 - редирект на www
    $www = '';
    if ($formValue['redirect'] == 2) {
        $www = 'www.';
    }
    $replace[] = str_replace('.', '\.', $www . $formValue['siteName']);

    $search[] = '[[URLSITE]]';
    // 1 - редирект без www
    // 2 - редирект на www
    $www = '';
    if ($formValue['redirect'] == 1) {
        $www = 'www.';
    }
    $replace[] = $www . $formValue['siteName'];

    $subFolderWoEndSlash = '';
    $commentForSubFolder = '';
    $subFolderIndex = '';
    $subFolder = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '/Ideal/Setup'));
    $subFolder = substr($subFolder, 0, strrpos($subFolder, '/'));
    $search[] = '[[SUBFOLDER]]';
    $replace[] = $subFolder;
    if ($subFolder != '') {
        $subFolderWoEndSlash = $subFolder;
        $commentForSubFolder = '#';
        // TODO доработать для разных суффиксов
        $subFolderIndex = 'index.html';
    }

    $search[] = '[[SUBFOLDER_WITHOUT_END_SLASH]]';
    $replace[] = $subFolderWoEndSlash;
    $search[] = '[[COMMENT_FOR_SUBFOLDER]]';
    $replace[] = $commentForSubFolder;
    $search[] = '[[SUBFOLDER_INDEX]]';
    $replace[] = $subFolderIndex;


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
    if (!file_exists(ROOT . '/js')) {
        mkdir(ROOT . '/js');
    }
    copyDir('../Library/bootstrap', ROOT . '/js/bootstrap');
    copyDir('../Library/jquery', ROOT . '/js/jquery');
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
 * Скопировать папку /Ideal/Setup/front/cms/ в указанную папку для CMS
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

    foreach ($config->structures as $v) {
        list($module, $structure) = explode('_', $v['structure']);
        $module = ($module == 'Ideal') ? '' : $module . '/';
        if (stream_resolve_include_path($module . 'Structure/' . $structure . '/install.php') !== false) {
            require_once $module . 'Structure/' . $structure . '/install.php';
        }
    }

    global $formValue;
    $db = \Ideal\Core\Db::getInstance();
    $db->insert($config->db['prefix'] . 'ideal_structure_user', array(
        'email' => $formValue['cmsLogin'],
        'reg_date' => time(),
        'password' => crypt($formValue['cmsPass']),
        'is_active' => 1,
        'prev_structure' => '0-2'
    ));

    if ($handle = opendir('../Template')) {
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                if (is_dir('../Template/' . $file)) {
                    $table = $config->db['prefix'] . 'ideal_template_' . strtolower($file);
                    $fields = require('../Template/' . $file . '/config.php');
                    $db->create($table, $fields['fields']);
                }
            }
        }
    }

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