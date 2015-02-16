<?php
/**
 * Загружаем сторонний файл дампа БД
 */

use Ideal\Core\Config;

$config = Config::getInstance();

//echo "<script>alert('44');</script>";

//require_once 'Library/jqueryFileUpload/server/php/UploadHandler.php';

//$upload_handler = new UploadHandler();

//echo json_encode('ggg');

                              /*
        if ($upload) {
            if (is_array($upload['tmp_name'])) {
                // param_name is an array identifier like "files[]",
                // $upload is a multi-dimensional array:
                foreach ($upload['tmp_name'] as $index => $value) {
                    $files[] = $this->handle_file_upload(
                        $upload['tmp_name'][$index],
                        $file_name ? $file_name : $upload['name'][$index],
                        $size ? $size : $upload['size'][$index],
                        $upload['type'][$index],
                        $upload['error'][$index],
                        $index,
                        $content_range
                    );
                }
            } else {
                // param_name is a single object identifier like "file",
                // $upload is a one-dimensional array:
                $files[] = $this->handle_file_upload(
                    isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                    $file_name ? $file_name : (isset($upload['name']) ?
                            $upload['name'] : null),
                    $size ? $size : (isset($upload['size']) ?
                            $upload['size'] : $this->get_server_var('CONTENT_LENGTH')),
                    isset($upload['type']) ?
                            $upload['type'] : $this->get_server_var('CONTENT_TYPE'),
                    isset($upload['error']) ? $upload['error'] : null,
                    null,
                    $content_range
                );
            }
        }

        $file = $_FILES['file']['type'];
        $response = array('file' => $file);
        echo json_encode($response);
        //echo json_encode(array('error'=>1));          */

$error = 0;        // код ошибки
$html = '';         // html код строки с загруженным файлом

if (isset($_FILES['file']['name'])) {

    // расширение загружаемого файла (без точки)
    $ext = end(explode('.', $_FILES['file']['name']));

    // Папка сохранения дампов
    //$backupPart = getDir($config->cms['tmpFolder'], '/backup/');
    $backupPart = stream_resolve_include_path($_GET['bf']);

    $time = time();

    // Имя файла дампа
    $dumpName = 'dump_' . date('Y.m.d_H.i.s', $time) . '_upload.sql.gz';

    // Полный путь
    $dumpName = $backupPart . DIRECTORY_SEPARATOR . $dumpName;

    if ( in_array($_FILES['file']['type'], array('application/gzip', 'application/octet-stream'))
        AND in_array($ext, array('gz', 'sql'))) {
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dumpName)) {
            // если файл не запакован, архивируем
            if ($ext != 'gz') {                $contents = file_get_contents($dumpName);
                $gz = gzopen($dumpName, 'w');
                gzwrite($gz, $contents);
                gzclose($gz);
            }
            // Формируем строку с новым файлом
            $html = '<tr id="' . $dumpName . '"><td><a href="" onClick="return downloadDump(\'' .
                addslashes($dumpName) . '\')"> ' .
                date('d.m.Y - H:i:s', $time) . ' (upload)'
                . '</a></td>'
            . '<td>'
            . '<button class="btn btn-info btn-xs" title="Импортировать" onclick="importDump(\'' .
                addslashes($dumpName) . '\'); return false;">'
                . '<span class="glyphicon glyphicon-upload"></span></button>&nbsp;'
            . '<button class="btn btn-danger btn-xs" title="Удалить" onclick="delDump(\'' .
                addslashes($dumpName) . '\'); return false;">'
                . '<span class="glyphicon glyphicon-remove"></span></button></td>'
            . '</tr>';
      	} else {            $error = 3;
      	}
	} else {	    $error = 2;
	}
} else {    $error = 1;
}
echo json_encode(array('html'=>$html, 'error'=>$error));

exit();
