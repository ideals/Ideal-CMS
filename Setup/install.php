<?php
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING); //| E_STRICT

ini_set('display_errors', 'On');

set_error_handler('installErrorHandler');

// Проверяем правильность Url
// Если Url неправильный (путь к скрипту содержит символы в неправильном регистре), делаем редирект с указанием правильного пути
$scriptDir = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']);
if ($scriptDir !== $_SERVER['REQUEST_URI']) {
    header("Location: $scriptDir");
}

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш.
define('DOCUMENT_ROOT', getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT']);

// Абсолютный адрес размещения админки
define('CMS_ROOT', $_SERVER['DOCUMENT_ROOT']
    . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '/Ideal/Setup')));

// Абсолютный адрес папки, в которой находится папка админки
define('ROOT', substr(CMS_ROOT, 0, strrpos(CMS_ROOT, '/')));

$fields = array(
    'siteName',
    'redirect',
    'cmsLogin',
    'cmsPass',
    'cmsPassRepeated',
    'dbHost',
    'dbLogin',
    'dbPass',
    'dbName',
    'dbPrefix'
);

$formValue = initFormValue($_POST, $fields);
$error = '';
$errorText = checkPost($_POST);

if ($error == '' && $errorText == 'Ok') {
    installCopyRoot();
    installCopyFront();
    createConfig();
    createTables();
    installFinished();
    header('Location: ../../index.php');
    exit;
}

@ header('Content-Type: text/html; charset=utf-8');
?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Установка Ideal CMS</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link href="../Library/bootstrap/css/bootstrap.css" rel="stylesheet">

        <script type="text/javascript" src="../Library/jquery/jquery-1.8.3.min.js"></script>
        <script type="text/javascript" src="../Library/bootstrap/js/bootstrap.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                $('#siteName').on('change keyup', function (e) {
                    var val = e.target.value;
                    val = val.toLowerCase();
                    if (val.substr(0, 4) == 'www.') {
                        val = val.substr(4);
                    }
                    $(".domain").each(function (indx, element) {
                        $(element).html(val);
                    });

                });
            });
        </script>

        <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
    </head>

    <body>

    <div class="container">

        <div class="navbar navbar-default">
            <div class="navbar-header">
                <span class="navbar-brand">Установка Ideal CMS в папку <?php echo CMS_ROOT; ?></span>
            </div>
        </div>



        <?php
        if ($error != '') {
            echo $error;
        }
        if ($errorText != '') {
            echo '<div class="alert">' . $errorText . '</div>';
        }
        ?>

        <form method="post" action="">
            <div class="col-lg-5">
                <div class="form-group">
                    <label for="siteName">Доменное имя сайта:</label>
                    <input type="text" class="form-control input-lg" id="siteName" name="siteName"
                           value="<?php echo $formValue['siteName']; ?>"/>
                </div>
                <div class="form-group">
                    <label>Редирект:</label>

                    <div style="margin-top:-11px; margin-bottom:17px;">
                        <label class="radio">
                            <input type="radio" name="redirect" id="options1" value="1" checked/>
                            www.<span class="domain"><?php echo $formValue['siteName']; ?></span> →
                            <span class="domain"><?php echo $formValue['siteName']; ?></span>
                        </label>
                        <label class="radio">
                            <input type="radio" name="redirect" id="options2" value="2"/>
                <span class="domain">
                <?php echo $formValue['siteName']; ?></span> → www.<span class="domain">
                <?php echo $formValue['siteName']; ?></span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="cmsLogin">Логин к админке:</label>
                    <input type="text" class="form-control input-lg" id="cmsLogin" name="cmsLogin"
                           value="<?php echo $formValue['cmsLogin']; ?>"/>
                </div>
                <div class="form-group">
                    <label for="cmsPass">Пароль к админке:</label>
                    <input type="password" class="form-control input-lg" id="cmsPass" name="cmsPass"
                           value="<?php echo $formValue['cmsPass']; ?>"/>
                </div>
                <div class="form-group">
                    <label for="cmsPassRepeated">Повторите пароль:</label>
                    <input type="password" class="form-control input-lg" id="cmsPassRepeated" name="cmsPassRepeated"
                           value="<?php echo $formValue['cmsPassRepeated']; ?>"/>
                </div>
            </div>
            <div class="col-lg-1"></div>
            <div class="col-lg-5">
                <div class="form-group">
                    <label for="dbHost">Хост БД:</label>
                    <input type="text" class="form-control input-lg" id="dbHost" name="dbHost"
                           value="<?php echo $formValue['dbHost']; ?>"/>
                </div>
                <div class="form-group">
                    <label class="control-label" for="dbLogin">Логин к БД:</label>
                    <input type="text" class="form-control input-lg" id="dbLogin" name="dbLogin"
                           value="<?php echo $formValue['dbLogin']; ?>"/>
                </div>
                <div class="form-group">
                    <label class="control-label" for="dbPass">Пароль к БД:</label>
                    <input type="password" class="form-control input-lg" id="dbPass" name="dbPass"
                           value="<?php echo $formValue['dbPass']; ?>"/>
                </div>
                <div class="form-group">
                    <label class="control-label" for="dbName">Имя БД:</label>
                    <input type="text" class="form-control input-lg" id="dbName" name="dbName"
                           value="<?php echo $formValue['dbName']; ?>"/>
                </div>
                <div class="form-group">
                    <label class="control-label" for="dbPrefix">Префикс таблиц:</label>
                    <input type="text" class="form-control input-lg" id="dbPrefix" name="dbPrefix"
                           value="<?php echo $formValue['dbPrefix']; ?>"/>
                </div>
            </div>
            <div class="form-actions text-center col-lg-11" style="margin-top:10px">
                <input class="btn btn-primary btn-lg" name="install" value="Установить" type="submit"/>
            </div>
        </form>

    </div>
    </body>
    </html>

<?php

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