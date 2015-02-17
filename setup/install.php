<?php
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING); //| E_STRICT

ini_set('display_errors', 'On');

set_error_handler('installErrorHandler');

// Проверяем правильность Url
// Если Url неправильный (путь к скрипту содержит символы в неправильном регистре),
// то делаем редирект с указанием правильного пути
$scriptDir = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']);
if ($scriptDir !== $_SERVER['REQUEST_URI']) {
    header("Location: $scriptDir");
}

require_once 'install_func.php';

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

    <script type="text/javascript" src="../Library/jquery/jquery.min.js"></script>
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
