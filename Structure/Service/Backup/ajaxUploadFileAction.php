<?php
/**
 * Загружаем сторонний файл дампа БД
 */

$error = 0;         // код ошибки
$html = '';         // html код строки с загруженным файлом

if (isset($_FILES['file']['name'])) {

    // Расширение загружаемого файла (без точки)
    $ext = substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1);   

    // Папка сохранения дампов
    $backupPart = stream_resolve_include_path($_GET['bf']);

    $time = time();

    // Имя файла дампа
    $dumpName = 'dump_' . date('Y.m.d_H.i.s', $time) . '_upload.sql';
    // Полный путь до дампа
    $dumpNameFull = $backupPart . DIRECTORY_SEPARATOR . $dumpName;
    // Полный путь до архива .gz
    $dumpNameGz = $dumpNameFull . '.gz';

    if (in_array($ext, array('gz', 'zip', 'sql'))) {
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dumpNameFull)) {
            
            // Если GZIP, переименовывем в .gz
            if ($ext == 'gz') {
                rename($dumpNameFull, $dumpNameGz);
            }
            // Если SQL, запаковывем в архив GZIP                
            if ($ext == 'sql') {
                rename($dumpNameFull, $dumpNameGz); 
                $contents = file_get_contents($dumpNameFull);
                $gz = gzopen($dumpNameGz, 'w');
                gzwrite($gz, $contents);
                gzclose($gz);                   
            }
            // Если ZIP, перепаковывем в GZIP
            if ($ext == 'zip') {               
                //Подключаем библиотеку
                require_once 'Library/pclzip.lib.php';
                $archive = new PclZip($dumpNameFull);     
                
                // Получаем список файлов в архиве
                $file_list = $archive->listContent();                                
                
                // Проверяем, чтобы в архиве был только один файл с расширением .sql
                if ($file_list !== 0) {                                            
                    if (count($file_list) == 1) {
                        $file = $file_list[0];        
                        if ($file['status'] == 'ok' && $file['size'] > 0) {                        
                            $ext = substr($file['filename'], strrpos($file['filename'], '.') + 1);                          
                            if ($ext == 'sql') {     
                                // Меняем обратные слэши на прямые
                                $rBackupPart = str_replace("\\", "/", $backupPart);                          
                                // Распаковываем архив в папку с бэкапами                                                                
                                $files = $archive->extract($rBackupPart);                                                                
                                if ($files != 0) {   
                                    // Распакованный файл
                                    $sqlName = $backupPart . DIRECTORY_SEPARATOR . $file['filename'];
                                    $contents = file_get_contents($sqlName);
                                    // Пакуем в .gz
                                    $gz = gzopen($dumpNameGz, 'w');
                                    gzwrite($gz, $contents);
                                    gzclose($gz);   
                                    // Удаляем загруженный файл
                                    unlink($dumpNameFull);
                                    // Удаляем распакованный файл
                                    unlink($sqlName);                                      
                                } else {
                                    // Ошибка: не удалось распаковать ZIP-архив
                                    $error = 35;
                                }                                
                            } else {
                                // Ошибка: расширение файла должно быть .sql
                                $error = 34;
                            }
                        } else {
                            // Ошибка: .sql файл в архиве поврежден или пустой
                            $error = 33;
                        }
                    } else {
                        // Ошибка: в архиве должен быть один .sql файл                       
                        $error = 32;
                    }  
                } else {
                    // Ошибка: не удалось получить список файлов в ZIP-архиве
                    $error = 31;
                }                           
            }
            
            // Формируем строку с новым файлом
            $html = '<tr id="' . $dumpNameGz . '"><td><a href="" onClick="return downloadDump(\'' .
                addslashes($dumpNameGz) . '\')"> ' .
                date('d.m.Y - H:i:s', $time) . ' (upload)'
                . '</a></td>'
            . '<td><button class="btn btn-info btn-xs" title="Импортировать" onclick="importDump(\'' .
                addslashes($dumpNameGz) . '\'); return false;">'
                . '<span class="glyphicon glyphicon-upload"></span></button>&nbsp;'
            . '<button class="btn btn-danger btn-xs" title="Удалить" onclick="delDump(\'' .
                addslashes($dumpNameGz) . '\'); return false;">'
                . '<span class="glyphicon glyphicon-remove"></span></button></td>'
            . '</tr>';
        } else {
            // Ошибка: не удалось переместить загруженный файл в указанную директорию
            $error = 3;
        }
    } else {
        // Ошибка: расширение файла должно быть .gz, .sql или .zip
        $error = 2;
    }
} else {
    // Ошибка: не удалось загрузить файл
    $error = 1;
}

echo json_encode(array('html'=>$html, 'error'=>$error));

exit();
