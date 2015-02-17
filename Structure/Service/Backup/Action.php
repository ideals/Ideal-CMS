<?php
use Ideal\Core\Config;

$config = Config::getInstance();

try {
    // Пытаемся определить полный путь к папке бэкапов, если это возможно
    // Проверяем временную папку и папку для бэкапов на существование, возможность создания и записи

    // Временная папка
    $tmpFolder = $config->cms['tmpFolder'];

    // Название папки бэкапа относительно временной папки
    $backupFolder = '/backup/';

    if ($tmpFolder == '') {
        throw new Exception('Не задана временная папка tmpDir в файле site_data.php');
    }

    // Определяем доступность временной папки
    $tmpFolder = DOCUMENT_ROOT . $tmpFolder;
    $tmpFull = stream_resolve_include_path($tmpFolder);

    // Проверяем существует ли временная папка и если нет, то пытаемся её создать
    if ($tmpFull === false) {
        if (mkdir($tmpFolder, 0755)) {
            $tmpFull = stream_resolve_include_path($tmpFolder);
        }
    }

    if ($tmpFull === false) {
        throw new Exception("Не удалось создать папку $tmpFolder для сохранения дампа базы");
    }

    if (!is_writable($tmpFull)) {
        throw new Exception("Папка $tmpFull недоступна для записи");
    }

    // Проверяем существует ли папка для создания бэкапов и если нет, то пытаемся её создать
    $backupFolder = $tmpFull . $backupFolder;
    $backupPart = stream_resolve_include_path($backupFolder);
    if ($backupPart === false) {
        if (mkdir($backupFolder, 0755)) {
            $backupPart = stream_resolve_include_path($backupFolder);
        }
    }

    if ($backupPart === false) {
        throw new Exception("Не удалось создать папку $backupFolder для сохранения дампа базы");
    }

    if (!is_writable($backupPart)) {
        throw new Exception("Папка $backupPart недоступна для записи");
    }

    // В результате $backupPart содержит полный путь к папке бэкапа

} catch (Exception $e) {
    echo '<div class="alert">' . $e->getMessage() . '</div>';
    return;
}
?>

<form class="form-inline">
    <button type="button" class="btn btn-success fileinput-button pull-right" style="margin-left:5px;"
            onclick="document.querySelector('input#uploadfile').click()">
        <i class="glyphicon glyphicon-plus"></i>
        <span>Загрузить файл</span>
    </button>
    <button type="submit" name="createMysqlDump" id="createMysqlDump" onclick="createDump(); return false;"
            class="btn btn-primary pull-right">
        Создать резервную копию БД
    </button>
    <span id='textDumpStatus'></span>
    <input id="uploadfile" style="visibility: collapse; width: 0;" type="file" name="file"
           onchange="upload(this.files[0])">
</form>

<div style="clear:right;"></div>

<?php
echo '<p>Папка с архивами: &nbsp;' . $backupPart . '</p>';
// Получение списка файлов
$dumpFiles = array();

if (is_dir($backupPart)) {
    echo '<table id="dumpTable" class="table table-hover">';
    $files = glob($backupPart . '/dump*.gz');
    rsort($files);
    foreach ($files as $file) {
        $file = basename($file);
        $year = substr($file, 5, 4);
        $month = substr($file, 10, 2);
        $day = substr($file, 13, 2);
        $hour = substr($file, 16, 2);
        $minute = substr($file, 19, 2);
        $second = substr($file, 22, 2);

        $file = $backupPart . DIRECTORY_SEPARATOR . $file;

        echo '<tr id="' . $file . '"><td>';
        echo '<a href="" onClick="return downloadDump(\'' . addslashes($file) . '\')"> ';
        echo "$day.$month.$year - $hour:$minute:$second";
        // если загруженный сторонний файл, дописываем в названии "(upload)"
        if (preg_match("/_upload/", $file)) {
            echo ' (upload)';
        }
        echo '</a></td>';
        echo '<td>';
        echo '<button class="btn btn-info btn-xs" title="Импортировать" onclick="importDump(\''
            . addslashes($file) . '\'); false;">';
        echo ' <span class="glyphicon glyphicon-upload"></span> ';
        echo '</button>&nbsp;';

        echo '<button class="btn btn-danger btn-xs" title="Удалить" onclick="delDump(\''
            . addslashes($file) . '\'); false;">';
        echo ' <span class="glyphicon glyphicon-remove"></span> ';
        echo '</button></td>';
        echo '</tr>';
    }
    echo '</table>';
}

?>

<script type="text/javascript">

    // Загрузка файла
    function upload(file) {
        // FormData
        var fd = new FormData();
        fd.append('file', file);
        $('#uploadfile').val('');
        // Url
        var url = window.location.href + "&action=ajaxUploadFile&bf=<?php echo addslashes($backupPart)?>";
        // Сообщение о процессе загрузки
        $('#textDumpStatus').removeClass().addClass('alert alert-info').html('Идёт загрузка файла...');
        // Загрузка
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: fd,
            cache: false,
            processData: false,
            contentType: false
        }).done(function (data) {
            if (data.error === false) {
                $('#textDumpStatus').removeClass().addClass('alert alert-success')
                    .html('Файл успешно загружен');
                $('#dumpTable').prepend(data.html);
            } else {
                $('#textDumpStatus').removeClass().addClass('alert alert-error')
                    .html(data.error);
            }
        }).fail(function () {
            $('#textDumpStatus').removeClass().addClass('alert alert-error')
                .html('Ошибка при загрузке файла');
        });
    }

    // Импорт дампа БД
    function importDump(nameFile) {
        if (confirm('Импортировать дамп БД:\n\n' + nameFile.split(/[\\/]/).pop() + '\n\n?')) {
            $('#textDumpStatus').removeClass().addClass('alert alert-info').html('Идёт импорт дампа БД');
            var path = window.location.href;
            $.ajax({
                url: path + "&action=ajaxImport",
                type: 'POST',
                data: {
                    name: nameFile
                },
                success: function (data) {
                    // Выводим сообщение
                    if (data.length == 0) {
                        $('#textDumpStatus').removeClass().addClass('alert alert-success')
                            .html('Дамп БД успешно импортирован');
                    } else {
                        $('#textDumpStatus').removeClass().addClass('alert alert-error')
                            .html(data);
                    }
                },
                error: function () {
                    $('#textDumpStatus').removeClass().addClass('alert alert-error')
                        .html('Не удалось импортировать файл');
                }
            })
        } else {
            // Do nothing!
        }
    }

    // Удаление файла
    function delDump(nameFile) {
        if (confirm('Удалить файл копии БД:\n\n' + nameFile.split(/[\\/]/).pop() + '\n\n?')) {
            var path = window.location.href;
            $.ajax({
                url: path + "&action=ajaxDelete",
                type: 'POST',
                data: {
                    name: nameFile
                },
                success: function (data) {
                    //Выводим сообщение
                    if (data == true) {
                        var el = document.getElementById(nameFile);
                        el.parentNode.removeChild(el);
                        $('#textDumpStatus').removeClass().addClass('alert alert-success')
                            .html('Файл успешно удалён');
                    } else {
                        $('#textDumpStatus').removeClass().addClass('alert alert-error')
                            .html('Ошибка при удалении файла');
                    }
                },
                error: function () {
                    $('#textDumpStatus').removeClass().addClass('alert alert-error').html('Не удалось удалить файл');
                }
            })
        } else {
            // Do nothing!
        }
    }

    // Создание дампа базы данных
    function createDump() {
        $('#textDumpStatus').removeClass().addClass('alert alert-info').html('Идёт создание копии БД');
        var path = window.location.href;
        $.ajax({
            url: path + "&action=ajaxCreateDump",
            type: 'POST',
            data: {
                createMysqlDump: true,
                backupPart: '<?php echo addslashes($backupPart)?>'
            },
            success: function (data) {
                //Выводим сообщение
                if (data.length > 1) {
                    $('#textDumpStatus').removeClass().addClass('alert alert-success')
                        .html('Копия БД создана');
                    $('#dumpTable').prepend(data);
                } else {
                    $('#textDumpStatus').removeClass().addClass('alert alert-error')
                        .html('Ошибка при создании копии БД');
                }
            },
            error: function () {
                $('#textDumpStatus').removeClass().addClass('alert alert-error').html('Не удалось создать копию БД');
            }
        })
    }

    function downloadDump(data) {
        var url = window.location.href;
        data = window.location.search.substr(1).split('?') + '&file=' + data + "&action=ajaxDownload";
        var method = 'get';

        // Разрезаем параметры в input'ы
        var inputs = '';
        jQuery.each(data.split('&'), function () {
            var pair = this.split('=');
            inputs += '<input type="hidden" name="' + pair[0] + '" value="' + pair[1] + '" />';
        });

        // Отправляем запрос
        jQuery('<form action="' + url + '" method="' + (method || 'post') + '">' + inputs + '</form>')
            .appendTo('body').submit().remove();

        return false;
    }
</script>
