<?php
/**
 * Сервис обновления IdealCMS
 * Должен присутствовать на каждом сайте, отвечает за получение новых версий cms и модулей,
 * их применение
 *
 */

ini_set('display_errors', 'Off');

$cmsFolder = $_POST['config'];
$subFolder = '';

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш.
define('DOCUMENT_ROOT', getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT']);

// В пути поиска по умолчанию включаем корень сайта, путь к Ideal и папке кастомизации CMS
set_include_path(
    get_include_path()
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Ideal.c/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Ideal/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Mods.c/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Mods/'
);

// Подключаем автозагрузчик классов
require_once 'Core/AutoLoader.php';

// Подключаем класс конфига
$config = Ideal\Core\Config::getInstance();

// Каталог, в котором находятся модифицированные скрипты CMS
$config->cmsFolder = $subFolder . $cmsFolder;

// Куда будет вестись лог ошибок. Варианты file|display|comment|firebug|email
$config->errorLog = 'firebug';

// Загружаем список структур из конфигурационных файлов структур
$config->loadSettings();


// Сервер обновлений
$srv = 'http://idealcms.ru/update';
$getFileScript = $srv . '/get.php';


if (is_null($config->tmpDir) || ($config->tmpDir == '')) {
    $message = array(
        'message ' => 'В настройках не указана папка для хранения временных файлов'
    );
    exit(json_encode($message));
}

// Папка для хранения загруженный файлов обновлений
$uploadDir = DOCUMENT_ROOT . $config->tmpDir . '/update/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        $message = array(
            'message ' => 'Не удалось создать папку' . $uploadDir
        );
        exit(json_encode($message));
    }
}

// Папка для разархивации файлов новой CMS
// Пример /www/example.com/tmp/Setup/Update
define('SETUP_DIR', $uploadDir . '/Setup');
if (!file_exists(SETUP_DIR)) {
    if (!mkdir(SETUP_DIR, 0755, true)) {
        $message = array(
            'message ' => 'Не удалось создать папку' . SETUP_DIR
        );
        exit(json_encode($message));
    }
}


$dbAdapter = Ideal\Core\Db::getInstance();


// Загрузка файла
if (isset($_POST['version']) && (isset($_POST['name']))) {
    $updateName  = $_POST['name'];
    $a =  $getFileScript . '?name=' . urlencode(serialize($updateName )) . '&ver=' . $_POST['version'];
    $file = file_get_contents($getFileScript . '?name=' . urlencode(serialize($updateName )) . '&ver=' . $_POST['version']);

    // Проверка получен ли ответ от сервера
    if (strlen($file) === 0) {
        $message = array(
            'message' => 'Файл пуст'
        );
        exit(json_encode($message));
    }

    // Если вместо файла найдено сообщение, выводим его
    $prefix = substr($file, 0, 5);
    if ($prefix === "(msg)"){
        $msg = substr($file, 5, strlen($file));
        $msg = json_decode($msg);
    }
    if (isset($msg->message)) {
        exit(json_encode($msg));
    }

    // Если получили md5
    if ($prefix === "(md5)"){
        $fileGet['md5'] = substr($file, 5, strpos($file, 'md5end') - 5);
        $fileGet['file'] = substr($file, strpos($file, 'md5end') + 6);
    }

    if (!isset($fileGet['md5'])) {
        $message = array(
            'message' => 'Не удалось получить хеш получаемого файла'
        );
        exit(json_encode($message));
    }

    // Путь к файлу архива
    // Пример /www/example.com/tmp/update
    $archive = $uploadDir . $updateName ;
    file_put_contents($archive, $fileGet['file']);

    if (md5_file($archive) != $fileGet['md5']) {
        $message = array(
            'message' => 'Полученный файл повреждён (хеш не совпадает)'
        );
        exit(json_encode($message));
    }
}

// После успешной загрузки архива, распаковываем его
if (isset($archive)) {
    $zip = new ZipArchive;
    $res = $zip->open($archive);
    // Очищаем папку перед распаковкой в неё фалов
    removeDirectory(SETUP_DIR, true);
    if ($res === true) {
        $zip->extractTo(SETUP_DIR);
        $zip->close();
        unlink($uploadDir . $updateName);
        $archive = true;
    } else {
        $message = array(
            'message' => 'Не получилось из-за ошибки #' . $res
        );
        exit(json_encode($message));
    }
}

// Если разархивирование произошло успешно
if ($archive === true){
    // Определяем путь к тому что мы обновляем, cms или модули
    if ($updateName  == "Ideal CMS") {
        // Путь к cms
        $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . "Ideal";
    } else {
        // Путь к модулям
        $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder. '/' . "Mods". '/' . $updateName;
    }
    // Переименовывем папку, которую собираемся заменить
    if (!rename($updateCore , $updateCore . '_old')) {
        $message = array(
            'message' => 'Не удалось переименовать папку ' . $updateCore
        );
        exit(json_encode($message));
    }
    // Перемещаем новую папку на место старой
    if (!rename(SETUP_DIR,  $updateCore)) {
        $message = array(
            'message' => 'Не удалось переименовать папку ' . $updateCore
        );
        exit(json_encode($message));
    }
    // Удаляем старую директорию
    removeDirectory($updateCore . '_old');

    $message = array(
        'message' => 'Обновление завершено успешно'
    );
    exit(json_encode($message));
}

/*
 * Удаление директории или её очистка
 * @param string $dir Папка которую необходимо удалить или очистить
 * @param bool $clear Если значение лож, то удаляем папку, если истина, очищаем
 *  */

function removeDirectory($dir, $clear = false) {
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? removeDirectory("$dir/$file") : unlink("$dir/$file");
    }
    if (!$clear) {
        return rmdir($dir);
    }
}
