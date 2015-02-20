<?php
/**
 * Загружаем сторонний файл дампа БД
 */

// Функция для выхода из скрипта
$exitScript = function($html, $error) {
    echo json_encode(array('html' => $html, 'error' => $error));
    exit;
};

if (!isset($_FILES['file']['name'])) {
    $exitScript('', 'Ошибка: не удалось загрузить файл');
}

$time = time();
$isOverride = false; // перезаписан ли файл

// Папка сохранения дампов
$backupPart = stream_resolve_include_path($_GET['bf']);

// Получаем версию админки
$versions = new \Ideal\Structure\Service\UpdateCms\Versions();
$nowVersions = $versions->getVersions();
$version = 'v' . $nowVersions['Ideal-CMS'];

// Имя загружаемого файла
$srcName = $_FILES['file']['name'];

// Расширение загружаемого файла (без точки)
$ext = substr($srcName, strrpos($srcName, '.') + 1);

// Проверяем соответствие имени загружаемого файла шаблону имени для дампа
preg_match("/dump_([0-9]{4}\.[0-9]{2}\.[0-9]{2}_[0-9]{2}\.[0-9]{2}\.[0-9]{2})_v[0-9a-z\.]{3,}\.[sql|zip]/is", $srcName, $m);

// Имя файла дампа
$timeName = (!empty($m[1])) ? $m[1] : date('Y.m.d_H.i.s', $time);
$dumpName = 'dump_' . $timeName . '_' . $version . '_upload.sql';

// Полный путь до дампа
$dumpNameFull = $backupPart . DIRECTORY_SEPARATOR . $dumpName;

// Полный путь до архива .gz
$dumpNameGz = $dumpNameFull . '.gz';

if (!in_array($ext, array('gz', 'zip', 'sql'))) {
    $exitScript('', 'Ошибка: расширение файла должно быть .gz, .sql или .zip');
}

if (file_exists($dumpNameGz)) {
    $exitScript('', 'Ошибка: файл с таким именем уже существует');
}

if (!move_uploaded_file($_FILES['file']['tmp_name'], $dumpNameFull)) {
    $exitScript('', 'Ошибка: не удалось переместить загруженный файл в папку');
}

switch ($ext) {
    // Просто переименновывем
    case 'gz':
        rename($dumpNameFull, $dumpNameGz);
        break;
    // Запаковываем .sql в архив GZIP
    case 'sql':
        rename($dumpNameFull, $dumpNameGz);
        $contents = file_get_contents($dumpNameGz);
        $gz = gzopen($dumpNameGz, 'w');
        gzwrite($gz, $contents);
        gzclose($gz);
        break;
    // Перепаковываем из ZIP в GZIP
    case 'zip':
        //Подключаем библиотеку
        require_once 'Library/pclzip.lib.php';
        $archive = new PclZip($dumpNameFull);

        // Получаем список файлов в архиве
        $file_list = $archive->listContent();

        if ($file_list == 0 || count($file_list) != 1) {
            unlink($dumpNameFull);  // удаляем загруженный файл
            $exitScript('', 'Ошибка: в архиве должен быть один .sql файл');
        }

        $file = $file_list[0];
        if (!($file['status'] == 'ok' && $file['size'] > 0)) {
            unlink($dumpNameFull);  // удаляем загруженный файл
            $exitScript('', 'Ошибка: .sql файл в архиве поврежден или пустой');
        }

        $ext = substr($file['filename'], strrpos($file['filename'], '.') + 1);
        if ($ext != 'sql') {
            unlink($dumpNameFull);  // удаляем загруженный файл
            $exitScript('', 'Ошибка: расширение файла должно быть .sql');
        }

        // Меняем обратные слэши на прямые
        $rBackupPart = str_replace("\\", "/", $backupPart);

        // Распаковываем архив в папку с бэкапами
        $files = $archive->extract($rBackupPart);

        if ($files == 0) {
            $exitScript('', 'Ошибка: не удалось распаковать ZIP-архив');
        }

        // Получаем содержимое распакованного файла
        $sqlName = $backupPart . DIRECTORY_SEPARATOR . $file['filename'];
        $contents = file_get_contents($sqlName);

        // Пакуем в .gz
        $gz = gzopen($dumpNameGz, 'w');
        gzwrite($gz, $contents);
        gzclose($gz);

        unlink($dumpNameFull);  // удаляем загруженный файл
        unlink($sqlName); // удаляем распакованный файл
        break;
}

// Формируем строку с новым файлом
$html = '<tr id="' . $dumpNameGz . '"><td><a href="" onClick="return downloadDump(\'' .
    addslashes($dumpNameGz) . '\')"> ' .
    str_replace('_', ' - ', $timeName) . ' - ' . $version . ' (upload)'
    . '</a></td>'
    . '<td><button class="btn btn-info btn-xs" title="Импортировать" onclick="importDump(\'' .
    addslashes($dumpNameGz) . '\'); return false;">'
    . '<span class="glyphicon glyphicon-upload"></span></button>&nbsp;'

    . '<button class="btn btn-danger btn-xs" title="Удалить" onclick="delDump(\'' .
    addslashes($dumpNameGz) . '\'); return false;">'
    . '<span class="glyphicon glyphicon-remove"></span></button>&nbsp;'

    . '<button id="' . $dumpNameGz . '_btn_cmt"
            class="tlp btn btn-default btn-xs btn-cmt"
            title="Добавить комментарий"
            onclick="showModal(\'' . addslashes($dumpNameGz) . '\'); false;">'
    . '<span class="glyphicon glyphicon-pencil"></span></button>&nbsp;'

    . '</td></tr>';

echo json_encode(array('html' => $html, 'error' => false));

exit;
