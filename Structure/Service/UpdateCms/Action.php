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

// todo Хранение версий

// Установленные версии CMS и модулей
$nowVersions = array(
    'CMS'       => '3.11',
    'Articles'  => '1.10',
    'Cabinet'   => '1.10',
    'Gallery'   => '1.10',
    'Shop'      => '1.10',
);

$domain = urlencode($config->domain);
$url = $getVersionScript . '?domain=' . $domain . '&ver=' .  urlencode(serialize($nowVersions));
//Перевожу в формат json для передачи в JS
$nowVersions = json_encode($nowVersions);


echo <<<SCREPT
    <script type="text/javascript" src="Ideal/Structure/Service/UpdateCms/jquery.jsonp-2.4.0.min.js"> </script>
SCREPT;
?>

<script type="text/javascript">
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
