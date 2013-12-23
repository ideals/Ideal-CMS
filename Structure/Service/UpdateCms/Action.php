<?php
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
    // Получаем файл README.md для cms
    $config = Config::getInstance();
    $mdFile = 'README.md';
    // Путь к файлу README.md для cms
    $cmsMdFileName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/' . $mdFile;
    // Получаем версию cms
    $versions['Ideal-CMS'] = getVersionFromFile($cmsMdFileName);

    // Ищем файлы README.md в модулях
    $modDirName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Mods';
    // Получаем версии модулей
    $modDirs = array_diff(scandir($modDirName), array('.', '..')); // получаем массив папок модулей
    foreach ($modDirs as $dir) {
        $modDir  = $modDirName . '/' . $dir  . '/' . $mdFile;
        $version = getVersionFromFile($modDir); // пытаемся извлечь номер версии из файла README.md
        if ($version) {
            $versions[$dir] = $version;
        }
    }
    return $versions;
}

function getVersionFromFile($cmsMdFileName)
{
    if (!file_exists($cmsMdFileName)) return false;

    // Получаем первую строку файла с наименованием и версией
    $file = fopen($cmsMdFileName, "r");
    $firstLine = trim(fgets($file));
    fclose($file);

    // Получаем номер версии из первой строки. Формат номера: пробел+v.+пробел+номер-версии+пробел-или-конец-строки
    preg_match_all('/\sv\.(\s*)(.*)(\s*)/i', $firstLine, $ver);

    // Если номер версии не удалось определить — выходим
    if (!isset($ver[2][0]) || ($ver[2][0] == '')) return false;

    return $ver[2][0];
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
                        $('<h4>').appendTo('#form-input').text(versions['message']);
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
                                $('<button>').appendTo('form:last').attr('onClick', buf).attr('class','btn').text('Обновить на версию ' + line['version'] + ' (' + line['date'] + ')');
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
