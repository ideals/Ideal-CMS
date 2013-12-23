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
<div id = "form-input">
</div>

<?php
// Сервер обновлений
$getVersionScript = 'http://idealcms/update/version.php';

$config = \Ideal\Core\Config::getInstance();

// Установленные версии CMS и модулей
$nowVersions = getVersions();

$domain = urlencode($config->domain);
// Сервер обновлений
$url = $getVersionScript . '?domain=' . $domain . '&ver=' .  urlencode(serialize($nowVersions));
// Переводим информацию о версиях в формат json для передачи в JS
$nowVersions = json_encode($nowVersions);

// Подключаем библиотеку для использования jsonp
echo <<<SCRIPT
    <script type="text/javascript" src="Ideal/Structure/Service/UpdateCms/jquery.jsonp-2.4.0.min.js"> </script>
SCRIPT;

function getVersions()
{
    // Получаем файл README.md для cms
    $config = Config::getInstance();
    $mdFile = 'README.md';
    // Путь к файлу README.md для cms
    $cmsMdFileName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/' . $mdFile;
    // Получаем версию cms
    $ver = getVersionFromFile($cmsMdFileName);

    // Ищем файлы README.md в модулях
    $modDirName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Mods';
    // Получаем версии модулей
    $modsVer = array();
    $modDirs = scandir($modDirName); // получаем массив папок модулей
    foreach ($modDirs as $k => $dir) {
        // Удаляем лишние элементы
        if ($dir == '.' || $dir == '..') {
            unset($modDirs[$k]);
            continue;
        }
        //
        $modDir  = $modDirName . '/' . $dir  . '/' . $mdFile;
        $modsVerOne = getVersionFromFile($modDir);
        if ($modsVerOne) {
            $modsVer = array_merge($modsVer, $modsVerOne);
        }
    }
    return $ver = array_merge($ver, $modsVer);
}

function getVersionFromFile($cmsMdFileName)
{
    if (!file_exists($cmsMdFileName)) return false;

    $file = fopen($cmsMdFileName, "r");
    // Получаем первую строку файла с наименованием и версией
    $ver = trim(fgets($file));
    fclose($file);
    // Разбиваем строку получая версию и наименование
    $ver = explode('v.', $ver, 2);
    // Получаем элемент массива, где ключ название модуля или cms, значение версия
    $ver[trim($ver[0])] = trim($ver[1]);
    // Удаляем из массива лишние элементы
    unset($ver[0]);
    unset($ver[1]);

    if (count($ver) !== 1) {
        return false;
    }

    return $ver;
}

?>


<script type="text/javascript">
/*
* Скрипт получения новых версий и создания представления
* */
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
                //Выводим сообщение и обновляем страницу
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
