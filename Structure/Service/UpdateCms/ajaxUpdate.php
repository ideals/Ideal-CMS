<?php
/*
 * Сервис обновления IdealCMS
 * Должен присутствовать на каждом сайте, отвечает за получение новых версий cms и модулей,
 * их применение
 * */

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
$srv = 'http://idealcms/update';
$getFileScript = $srv . '/get.php';

// Папка для хранения загруженный файлов обновлений
$uploadDir = '/tmp/update/';

// Папка для разархивации файлов новой CMS
// Пример C:\www\idealcms\tmp\Setup\Update
define('UPDATES_DIR', DOCUMENT_ROOT . '/tmp/Setup/Update');

$dbAdapter = Ideal\Core\Db::getInstance();


// Загрузка файла
if (isset($_POST['version']) && (isset($_POST['name']))) {
    $udName  = $_POST['name'];
    $a =  $getFileScript . '?name=' . urlencode(serialize($udName )) . '&ver=' . $_POST['version'];
    $file = file_get_contents($getFileScript . '?name=' . urlencode(serialize($udName )) . '&ver=' . $_POST['version']);

    // Если вместо файла найдено сообщение, выводим его
    $prefix = substr($file, 0, 5);
    if ($prefix === "(msg)"){
        $msg = substr($file, 5, strlen($file));
        $msg = json_decode($msg);
    }
    if (isset($msg->message)) {
        exit(json_encode($msg));
    }

    if (strlen($file) === 0) {
        $message = array(
            'message' => 'Файл пуст'
        );
        exit(json_encode($message));
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
    // Пример C:\www\idealcms\tmp\update
    $archive = $_SERVER['DOCUMENT_ROOT'] . $uploadDir . $udName ;
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
    removeDirectory(UPDATES_DIR, true);
    if ($res === true) {
        $zip->extractTo(UPDATES_DIR);
        $zip->close();
        unlink($_SERVER['DOCUMENT_ROOT'] . $uploadDir . $udName );
        $archive = true;
    } else {
        $message = array(
            'message' => 'Не получилось из-за ошибки #' . $res
        );
        exit(json_encode($message));
    }
}

// todo Установка апдейтов из папки Ideal/Setup/Update

// Если разархивирование вышло успешно
if ($archive === true){
    $ds = DIRECTORY_SEPARATOR;
    // Определяем путь к тому что мы обновляем, cms или модули
    if ($udName  == "Ideal CMS") {
        // Путь к cms
        $updateCore = DOCUMENT_ROOT . $ds . $config->cmsFolder . $ds . "Ideal";
    } else {
        // Путь к модулям
        $updateCore = DOCUMENT_ROOT . $ds . $config->cmsFolder. $ds . "Mods". $ds . $udName;
    }
    // Переименовывем папку, которую собираемся заменить
    if (!rename($updateCore , $updateCore . '_old')) {
        $message = array(
            'message' => 'Не удалось обновить'
        );
        exit(json_encode($message));
    }
    // Перемещаем новую папку на место старой
    if (!rename(UPDATES_DIR,  $updateCore)) {
        $message = array(
            'message' => 'Не удалось обновить'
        );
        exit(json_encode($message));
    }
    // Удаляем старую директорию
    removeDirectory($updateCore . '_old');

    $message = array(
        'message' => 'Обновление прошло успешно'
    );
    exit(json_encode($message));
}


function getExtension($filename) {
    $filenameParts = explode('.', $filename);
    return $filenameParts[count($filenameParts)-1];
}

function processSql($filename) {
    global $dbAdapter;
    $sql = file_get_contents(UPDATES_DIR . '/' . $filename);
    $sqlArr = explode(";\n",trim($sql));
    if (!empty($sqlArr)) {
        foreach ($sqlArr as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    $dbAdapter->query($query);
                } catch (Zend_Db_Statement_Exception $e) {
                    echo 'Error in ' . $filename . ': ' . $e->getMessage();
                    return;
                }
            }
        }
    }

    updateChangelog($filename);
}

function processPhp($filename) {
    exec('php ' . UPDATES_DIR . '/' . $filename);

    updateChangelog($filename);
}

function updateChangelog($filename) {
    global $dbAdapter;
    $dbAdapter->query('INSERT INTO `i_w8_changelog` (`filename`) VALUES ("' . $filename . '")');
}

/*
 * Удаление директории или её очистка
 * @param string $dir Папка которую необходимо удалить или очистить
 * @param bool $clear Если значение лож, то удаляем папку, если истина, очищаем
 *  */
function removeDirectory($dir, $clear = false) {
    if ($objs = glob($dir."/*")) {
        foreach($objs as $obj) {
            is_dir($obj) ? removeDirectory($obj) : unlink($obj);
        }
    }
    if (!$clear) {
        rmdir($dir);
    }
}
