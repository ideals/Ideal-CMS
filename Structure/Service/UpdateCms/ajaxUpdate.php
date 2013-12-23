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
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/IdealCustom/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Ideal/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/ModulesCustom/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Modules/'
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
define('UPDATES_DIR', DOCUMENT_ROOT . $cmsFolder . '/Ideal/Setup/Update');

$dbAdapter = Ideal\Core\Db::getInstance();


// Загрузка файла
if (isset($_POST['version']) && (isset($_POST['name']))) {
    $file = file_get_contents($getFileScript . '?name=' . urlencode(serialize($_POST['name'])) . '&ver=' . $_POST['version']);

    // Если вместо файла найдено сообщение, выводим его
    $msg = substr($file, 0, 5);
    if ($msg === "(msg)"){
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

    file_put_contents($_SERVER['DOCUMENT_ROOT'] . $uploadDir . 'tempFile', $file);
    $archive = $_SERVER['DOCUMENT_ROOT'] . $uploadDir . 'tempFile';
}


if (isset($archive)) {
    $zip = new ZipArchive;
    $res = $zip->open($archive);
    // todo Стирать файлы перед распаковкой
    if ($res === true) {
        $zip->extractTo(UPDATES_DIR);
        $zip->close();
        unlink($_SERVER['DOCUMENT_ROOT'] . $uploadDir.'tempFile');
        $archive = true;
    } else {
        $message = array(
            'message' => 'Не получилось из-за ошибки #' . $res
        );
        exit(json_encode($message));
    }
}

// todo Установка апдейтов из папки Ideal/Setup/Update


if ($archive === true){
    // Получаем список файлов апдейтов
    $updates = array();
    if (is_dir(UPDATES_DIR)) {
        if ($dh = opendir(UPDATES_DIR)) {
            while (($file = readdir($dh)) !== false) {
                if (is_file(UPDATES_DIR . '/' . $file)) {
                    $updates[] = $file;
                }
            }
            closedir($dh);
        }
    }
    sort($updates);

    //Получаем список файлов выполненных ранее апдейтов
    $applied = $dbAdapter->queryArray('SELECT `filename`  FROM `i_w8_changelog`');
    foreach($applied as $key => $value){
        $applied[$key] = $value['filename'];
    }

    // Применяем все новые апдейты
    $newApplied = array();
    foreach ($updates as $update) {
        if (!in_array($update, $applied)) {
            switch (getExtension($update)) {
                case 'sql':
                    processSql($update);
                    $newApplied[] = $update;
                    break;
                case 'php':
                    processPhp($update);
                    $newApplied[] = $update;
                    break;
                default:
                    // Все остальные расширения пропускаем
            }
        }
    }
    $message = array(
        'message' => "New applied updates:\n" . implode("\n", $newApplied) . "\n \nTotal applied: " . count($newApplied)
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