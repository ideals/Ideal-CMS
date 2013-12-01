<?php
use Ideal\Core\Config;
$config = Config::getInstance();

try {
    // Пытаемся определить полный путь к папке бэкапов, если это возможно
    $backupPart = getDir($config->tmpDir, '/backup/');
} catch (Exception $e) {
    echo '<div class="alert">' . $e->getMessage() . '</div>';
    return;
}
?>

<form class="form-inline">
    <button type="submit" name="createMysqlDump" id="createMysqlDump" onclick="createDump(); return false;"
            class="btn btn-primary pull-right">
        Создать backup базы
    </button>
    <span id='textDumpStatus'></span>
</form>

<div style="clear:right;"></div>

<?php
echo '<p>Папка с архивами: &nbsp;' . $backupPart . '</p>';
// Получение списка файлов
$dumpFiles = array();

if ($dh = opendir($backupPart)) {
    echo '<table id="dumpTable" class="table table-hover">';
    while (($file = readdir($dh)) !== false) {
        if (strripos($file, 'dump_') === false) continue;
        //$dumpFiles[] = $file;
        //$fn = str_replace('dump_', '',$file);
        $year = substr($file,5,4);
        $month = substr($file,10,2);
        $day = substr($file,13,2);
        $hour = substr($file,16,2);
        $minute = substr($file,19,2);
        $second = substr($file,22,2);

        echo '<tr id="' . $file . '"><td>';
        echo '<a href="" onClick="return downloadDump(\'' . addslashes($backupPart . '/' . $file) . '\')"> ';
        echo "$day/$month/$year - $hour:$minute:$second" . '</a></td>';
        echo '<td>';
        echo '<button class="btn btn-danger btn-mini" title="Удалить" onclick="delDump(\'' . $file . '\'); false;">';
        echo ' <i class="icon-remove icon-white"></i> ';
        echo '</button></td>';
        echo '</tr>';
    }
    closedir($dh);
    echo '</table>';
}



/**
 * Функция получения полного пути к папке бэкапа
 * Проверяем временную папку и папку для бэкапов на существование, возможность создания и записи
 * @param string $tmpDir Временная папка
 * @param string $backupDir Название папки бэкапа относительно временной папки
 * @return string Полный путь к папке бэкапа
 * @throws Exception Исключение, содержащее текст возникшей ошибки
 */
function getDir($tmpDir, $backupDir)
{
    if ($tmpDir == '') {
        throw new Exception('Не задана временная папка tmpDir в файле site_data.php');
    }

    // Определяем доступность временной папки
    $tmpDir = DOCUMENT_ROOT . $tmpDir;
    $tmpFull = stream_resolve_include_path($tmpDir);

    // Проверяем существует ли временная папка и если нет, то пытаемся её создать
    if ($tmpFull === false) {
        if (mkdir($tmpDir, 0755)) {
            $tmpFull = stream_resolve_include_path($tmpDir);
        }
    }

    if ($tmpFull === false) throw new Exception("Не удалось создать папку $tmpDir для сохранения дампа базы");

    if (!is_writable($tmpFull)) throw new Exception("Папка $tmpFull недоступна для записи");

    // Проверяем существует ли папка для создания бэкапов и если нет, то пытаемся её создать
    $backupDir = $tmpFull . $backupDir;
    $backupFull = stream_resolve_include_path($backupDir);
    if ($backupFull === false) {
        if (mkdir($backupDir, 0755)) {
            $backupFull = stream_resolve_include_path($backupDir);
        }
    }

    if ($backupFull === false) throw new Exception("Не удалось создать папку $backupDir для сохранения дампа базы");

    if (!is_writable($backupFull)) throw new Exception("Папка $backupFull недоступна для записи");

    return $backupFull;
}

?>

<script type="text/javascript">

// Удаление файла
function delDump(idFile) {
    var nameFile = dir + idFile;
    if (confirm('Удалить файл дампа базы?')) {
        var path = window.location.href;
        $.ajax({
            url: path + "&action=ajaxDelete",
            type: 'POST',
            data: {
                name: nameFile
            },
            success: function(data){
                //Выводим сообщение
                var message = data;
                if (message == true) {
                    var el = document.getElementById(idFile);
                    el.parentNode.removeChild(el);
                    $('#textDumpStatus').removeClass().addClass('alert alert-success').html('Файл успешно удалён');
                } else {
                    $('#textDumpStatus').removeClass().addClass('alert alert-error').html('Ошибка при удалении файла');
                }
            },
            error: function() {
                $('#textDumpStatus').removeClass().addClass('alert alert-error').html('Не удалось удалить файл');
            }
        })
    } else {
        // Do nothing!
    }
}

// Создание дампа базы данных
function createDump() {
    $('#textDumpStatus').removeClass().addClass('alert alert-info').html('Идёт создание дампа базы');
    var path = window.location.href;
    $.ajax({
        url: path + "&action=ajaxCreateDump",
        type: 'POST',
        data: {
            createMysqlDump: true,
            backupPart: '<?php echo addslashes($backupPart)?>'
        },
        success: function(data){
            //Выводим сообщение
            var message = data;
            if (message.length > 1) {
                $('#textDumpStatus').removeClass().addClass('alert alert-success').html('Дамп базы создан');
                $('#dumpTable').append(data);
            } else {
                $('#textDumpStatus').removeClass().addClass('alert alert-error').html('Ошибка при создании дампа базы');
            }
        },
        error: function() {
            $('#textDumpStatus').removeClass().addClass('alert alert-error').html('Не удалось создать дамп базы');
        }
    })
}

function downloadDump(data)
{
    var url = window.location.href;
    data = window.location.search.substr(1).split('?') + '&file=' + data + "&action=ajaxDownload";
    method = 'get';

    // Разрезаем параметры в input'ы
    var inputs = '';
    jQuery.each(data.split('&'), function(){
        var pair = this.split('=');
        inputs += '<input type="hidden" name="'+ pair[0] +'" value="'+ pair[1] +'" />';
    });

    // Отправляем запрос
    jQuery('<form action="'+ url +'" method="'+ (method||'post') +'">'+inputs+'</form>')
        .appendTo('body').submit().remove();

    return false;
}
</script>