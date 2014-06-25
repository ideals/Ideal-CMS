<?php
/*
 * 1 Проверка файла update.log на возможность записи
 * 2 Если update.log пуст или не содержит обновлений о каком либо модуле вносим в него данные из Readme.md
 * в формате
 *      [updateInfo]
 *      name=Наименование папки
 *      ver=название + Версия
 * 3 Получаем версии из update.log, а также, при наличии, названия ранее выполненных файлов
 * 4 Выполнение скриптов для текущей версии (оставшихся или всех), записывая при этом каждый успешно выполненный скрипт
 * 5 Запись в update.log новой версии
 * */

/**
 * Сервис обновления IdealCMS
 * Должен присутствовать на каждом сайте, отвечает за представление информации об обновлении
 */

use Ideal\Core\Config;

?>

<p id="message">
    Внимание! Обновление в рамках одинакового первого номера происходит автоматически.<br />
    Обновление на другой первый номер версии требует ручного вмешательства.<br />
    <hr />
</p>
<div id="form-input">
</div>

<?php
// Сервер обновлений
$getVersionScript = 'http://idealcms.ru/update/version.php';

$config = \Ideal\Core\Config::getInstance();

// Сообщение
$msg = '';

// todo Хранение версий

// Установленные версии CMS и модулей
$nowVersions = getVersions();

$domain = urlencode($config->domain);
// Сервер обновлений
$url = $getVersionScript . '?domain=' . $domain . '&ver=' .  urlencode(serialize($nowVersions));
// Переводим информацию о версиях в формат json для передачи в JS
$nowVersions = json_encode($nowVersions);

// Подключаем библиотеку для использования jsonp
echo '<script type="text/javascript" src="Ideal/Structure/Service/UpdateCms/jquery.jsonp-2.4.0.min.js"> </script>';

function getVersions()
{
    $config = Config::getInstance();
    // Путь к файлу README.md для cms
    $mods['Ideal-CMS'] = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal';


    // Ищем файлы README.md в модулях
    $modDirName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Mods';
    // Получаем разделы
    $modDirs = array_diff(scandir($modDirName), array('.', '..')); // получаем массив папок модулей
    foreach ($modDirs as $dir) {
        // Исключаем разделы, явно не содержащие модули
        if ((stripos($dir, '.') === 0) || (is_file($modDirName . '/' . $dir))) {
            unset($mods[$dir]);
            continue;
        }
        $mods[$dir] = $modDirName . '/' . $dir;
    }
    // Получаем версии для каждого модуля и CMS из update.log
    $versions = getVersionFromFile($mods);


    return $versions;
}

/**
 * Получение версии из файла
 *
 * @param string $mods Папки с модулями и CMS
 * @param string $msg Сообщение $msg Сообщение
 * @return bool
 */
function getVersionFromFile($mods)
{
    global $msg;
    $config = Config::getInstance();
    // Файл лога обновлений
    $log = $config->cmsFolder . '/' . 'update.log';

    // Проверяем файл update.log
    if (file_put_contents($log, '', FILE_APPEND) === false){
        if (file_exists($log)) {
            $msg = 'Файл ' . $log . ' недоступен для записи';
        } else {
            $msg = 'Не удалось создать файл ' . $log;
        }
        return false;
    };

    //Получаем версии
    if (filesize($log) == 0) {
        $version = getVersionFromReadme($mods);
        putVersionLog($version, $log);
    } else {
        $version = getVersionFromLog($mods, $log);
    }
    return $version;
}

function getVersionFromLog($mods, $log) {
    $linesLog = file($log);
    $version = array();
    for($i = count($linesLog) - 1; $i>=0; $i--) {
        // Удаление спец символов конца строки (необходимость в таком удалении возникает в ОС Windows)
        $linesLog[$i] = rtrim($linesLog[$i]);
        if ($linesLog[$i] != '[updateInfo]') continue;
        $buf['name'] = explode('=', $linesLog[$i + 1]);
        if (isset($version[$buf['name']['1']])) continue;
        $buf['ver'] = explode('=', $linesLog[$i + 2]);
        $version[$buf['name'][1]] = $buf['ver']['1'];
    }

    return $version;
}

function putVersionLog($version, $log)
{
    $lines = array();
    foreach ($version as $k => $v) {
        $lines[] = "[updateInfo]";
        $lines[] = "name={$k}";
        $lines[] = "version={$v}";
    }
    file_put_contents($log, implode("\r\n", $lines));
}

function getVersionFromReadme($mods)
{
    global $msg;
    // Получаем файл README.md для cms
    $mdFile = 'README.md';
    foreach ($mods as $k => $v) {
        $lines = file($v . '/' . $mdFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (($lines == false) || (count($lines) == 0)){
            $msg = 'Не удалось получить версию из ' . $v . '/' . $mdFile;
            return false;
        }
        // Получаем номер версии из первой строки. Формат номера: пробел+v.+пробел+номер-версии+пробел-или-конец-строки
        preg_match_all('/\sv\.(\s*)(.*)(\s*)/i', $lines[0], $ver);
        // Если номер версии не удалось определить — выходим
        if (!isset($ver[2][0]) || ($ver[2][0] == '')) {
            $msg = 'Ошибка при разборе строки с версией файла';
            return false;
        }

        $version[$k] = $ver[2][0];
    }
    return $version;
}
?>


<script type="text/javascript">
/**
 * Скрипт получения новых версий и создания представления
 */
    $.jsonp({
        url: '<?php echo $url ?>',
        callbackParameter: 'callback',
        dataType: 'jsonp',
        success: function(versions){
            var nowVersions = '<?php echo  $nowVersions ?>';
                    nowVersions = $.parseJSON(nowVersions);

                    if (versions['message'] !== undefined) {
                        $('<h4>').appendTo('#form-input').html(versions['message']);
                        nowVersions = null;
                    };

                    $.each(nowVersions, function(key,value) {
                        // Выводим заголовок с именем обновляемого модуля
                        var buf = key + " " + value;
                        $('<h4>').appendTo('#form-input').text(buf);
                        var update = versions[key];

                        if ((update == undefined) || (update == "")){
                            $('<p>').appendTo('#form-input').text("Обновление не требуется.");
                            return true;
                        }
                        if (update['message'] !== undefined){
                            $('<p>').appendTo('#form-input').text(update['message']);
                            return true;
                        }

                        $('<form>').appendTo('#form-input').attr('class','update-form form-inline').attr('action','javascript:void(0)').attr('method','post');

                        $.each(update, function(keyLine, line){
                            buf = 'updateModule("' + key + '","' + line['version'] + '")';
                            $('<button>').appendTo('form:last').attr('class','btn').attr('onClick', buf).attr('class','btn').text('Обновить на версию ' + line['version'] + ' (' + line['date'] + ')');
                            if (line['danger']) {
                                $('button:last').attr('class','btn btn-danger')
                            }
                            $('button:last').after('&nbsp; &nbsp;');
                        });
                    });
        },
        error: function(){
            $('#message').after('<p><b>Не удалось соединиться с сервером</b></p>');
        }
    });


/*
 * Скрипт обновления cms и модулей
 * */
    function updateModule(moduleName, version)
    {
        $.ajax({
            url: 'Ideal/Structure/Service/UpdateCms/ajaxUpdate.php',
            type: 'POST',
            data: {
                name: moduleName,
                version: version,
                config: '<?php echo $config->cmsFolder; ?>'
            },
            success: function(data){
                // Выводим сообщение и обновляем страницу
                var message = $.parseJSON(data);
                alert(message['message']);
                location.reload();
            },
            error: function() {
                alert('Не удалось произвести обновление');
            }
        })
    }
</script>
