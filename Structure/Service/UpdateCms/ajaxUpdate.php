<?php
/*
 * 1 Получаем версии из update.log, а также, при наличии, названия ранее выполненных файлов
 * 2 Если не удалось получить доступ к файлу выводим сообщение об ошибке
 * 4 Выполнение скриптов для текущей версии (оставшихся или всех), записывая при этом каждый успешно выполненный скрипт
 * 5 Дозапись в update.log новой версии (берётся название следующего раздела с обновлениями)
 * 6 Если новая версия не соответствует необходимой, возвращаемся к пункту 1
 * */

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

// Файл лога обновлений
$log = $config->cmsFolder . '/' . 'update.log';
if (!file_exists($log)) {
    uExit('Файл лога обновлений не существует ' . $log);
}
if (file_put_contents($log, '', FILE_APPEND) === false){
    uExit('Файл ' . $log . ' недоступен для записи');
}

if (is_null($config->tmpDir) || ($config->tmpDir == '')) {
    uExit('В настройках не указана папка для хранения временных файлов');
}

// Папка для хранения загруженный файлов обновлений
$uploadDir = DOCUMENT_ROOT . $config->tmpDir . '/update/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        uExit('Не удалось создать папку' . $uploadDir);
    }
}

// Папка для разархивации файлов новой CMS
// Пример /www/example.com/tmp/Setup/Update
define('SETUP_DIR', $uploadDir . '/Setup');
if (!file_exists(SETUP_DIR)) {
    if (!mkdir(SETUP_DIR, 0755, true)) {
        uExit('Не удалось создать папку' . SETUP_DIR);
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
        uExit('Файл пуст');
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
        uExit('Не удалось получить хеш получаемого файла');
    }

    // Путь к файлу архива
    // Пример /www/example.com/tmp/update
    $archive = $uploadDir . $updateName ;
    file_put_contents($archive, $fileGet['file']);

    if (md5_file($archive) != $fileGet['md5']) {
        uExit('Полученный файл повреждён (хеш не совпадает)');
    }
}

// После успешной загрузки архива, распаковываем его
if (isset($archive)) {
    $zip = new ZipArchive;
    $res = $zip->open($archive);
    // Очищаем папку перед распаковкой в неё файлов
    removeDirectory(SETUP_DIR, true);
    if ($res === true) {
        $zip->extractTo(SETUP_DIR);
        $zip->close();
        unlink($uploadDir . $updateName);
        $archive = true;
    } else {
        uExit('Не получилось из-за ошибки #' . $res);
    }
}

// Если разархивирование произошло успешно
if ($archive === true){
    // Определяем путь к тому что мы обновляем, cms или модули
    if ($updateName  == "Ideal-CMS") {
        // Путь к cms
        $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . "Ideal";
    } else {
        // Путь к модулям
        $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder. '/' . "Mods". '/' . $updateName;
    }
    // Переименовывем папку, которую собираемся заменить
    if (!rename($updateCore , $updateCore . '_old')) {
        uExit('Не удалось переименовать папку ' . $updateCore);
    }
    // Перемещаем новую папку на место старой
    if (!rename(SETUP_DIR,  $updateCore)) {
        uExit('Не удалось переименовать папку ' . $updateCore);
    }
    //Запускаем выполнение скриптов и запросов
    runProcess();
    // Удаляем старую директорию
    removeDirectory($updateCore . '_old');

    exit('Обновление завершено успешно');
}

function runProcess()
{

}

/**
 * Завершение выполнения скрипта с выводом сообщения
 *
 * @param string $msg Сообщение которое нужно передать в качестве результата работы скрипта
 * @throws Exception если аргумент функции не является строкой
 */
function uExit($msg) {
    if (!is_string($msg)) {
        throw new Exception("Необходим аргумент типа строка");
    }
    $message = array(
        'message ' => $msg
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
