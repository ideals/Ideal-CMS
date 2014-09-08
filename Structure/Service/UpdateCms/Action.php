<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

/**
 * Сервис обновления IdealCMS и модулей
 *
 * ЧАСТЬ ПЕРВАЯ. ОТОБРАЖЕНИЕ УСТАНОВЛЕННЫХ ВЕРСИЙ И ВОЗМОЖНОСТЕЙ ОБНОВЛЕНИЯ
 * 1. Проверка файла update.log на возможность записи
 * 2. Считываем номера версий CMS и модулей из update.log
 * 3. Если update.log пуст или не содержит обновлений о каком-либо модуле вносим в него данные из README.md
 *    в формате
 *      Installed Наименование-папки-модуля v. Версия
 * 4. С сервера обновлений считываем доступные обновления и отображаем их в виде кнопок обновления по отдельности
 *    для каждого модуля
 *
 * ЧАСТЬ ВТОРАЯ. ОБНОВЛЕНИЕ МОДУЛЯ
 * 1. По нажатию на кнопку обновления у CMS или модуля скачиваем и распаковываем новую версию модуля
 * 2. Из update.log считываем последнюю установленную версию и последний установленный скрипт модуля
 * 3. Читаем список папок скриптов обновления, сортируем их по номеру версии
 * 4. В цикле по папкам читаем их содержимое, сортируем
 * 5. Выполняем скрипты в этих папках начиная со следующего после установленного скрипта
 */
?>

<p id="message">
    Внимание! Обновление в рамках одинакового первого номера происходит автоматически.<br/>
    Обновление на другой первый номер версии требует ручного вмешательства.<br/>
<hr/>
</p>

<?php
// Сервер обновлений
$getVersionScript = 'http://idealcms.ru/update/version.php';

$config = \Ideal\Core\Config::getInstance();
$updateModel = new \Ideal\Structure\Service\UpdateCms\Model();

// Получаем установленные версии CMS и модулей
$nowVersions = $updateModel->getVersions();

$domain = urlencode($config->domain);

// Сервер обновлений
$url = $getVersionScript . '?domain=' . $domain . '&ver=' . urlencode(serialize($nowVersions));

// Переводим информацию о версиях в формат json для передачи в JS
$nowVersions = json_encode($nowVersions);

// Подключаем библиотеку для использования JSONP
echo '<script type="text/javascript" src="Ideal/Structure/Service/UpdateCms/jquery.jsonp-2.4.0.min.js"> </script>';

$msg = $updateModel->getErrorText();
if ($msg !== '') {
    echo '<div class="alert-error">' . $msg . "</div>\n";
}
?>

<div id="form-input"></div>

<script type="text/javascript">

    /** Получение новых версий и создания представления */
    $.jsonp({
        url: '<?php echo $url ?>',
        callbackParameter: 'callback',
        dataType: 'jsonp',
        success: function (versions) {
            var nowVersions = '<?php echo  $nowVersions ?>';
            nowVersions = $.parseJSON(nowVersions);

            if (versions['message'] !== undefined) {
                $('<h4>').appendTo('#form-input').html(versions['message']);
                nowVersions = null;
            }

            $.each(nowVersions, function (key, value) {
                // Выводим заголовок с именем обновляемого модуля
                var buf = key + " " + value;
                $('<h4>').appendTo('#form-input').text(buf);
                var update = versions[key];

                if ((update == undefined) || (update == "")) {
                    $('<p>').appendTo('#form-input').text("Обновление не требуется.");
                    return true;
                }
                if (update['message'] !== undefined) {
                    $('<p>').appendTo('#form-input').text(update['message']);
                    return true;
                }

                $('<form>')
                    .appendTo('#form-input')
                    .attr('class', 'update-form form-inline')
                    .attr('action', 'javascript:void(0)')
                    .attr('method', 'post');

                $.each(update, function (keyLine, line) {
                    buf = 'updateModule("' + key + '","' + line['version'] + '")';
                    $('<button>')
                        .appendTo('form:last').attr('class', 'btn')
                        .attr('onClick', buf).attr('class', 'btn')
                        .text('Обновить на версию ' + line['version'] + ' (' + line['date'] + ')');
                    if (line['danger']) {
                        $('button:last').attr('class', 'btn btn-danger')
                    }
                    $('button:last').after('&nbsp; &nbsp;');
                });
            });
        },
        error: function () {
            $('#message').after('<p><b>Не удалось соединиться с сервером</b></p>');
        }
    });

    /** Обновление CMS или модуля */
    function updateModule(moduleName, version) {
        $.ajax({
            url: 'index.php?par=<?php echo $_GET['par']; ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                mode: 'ajax',
                controller: '\\Ideal\\Structure\\Service\\UpdateCms',
                action: 'ajaxDownload',
                name: moduleName,
                version: version,
                config: '<?php echo $config->cmsFolder; ?>'
            },
            success: function (data) {
                // Выводим сообщение и обновляем страницу
                alert(data.message);
                location.reload();
            },
            error: function () {
                alert('Не удалось произвести обновление');
            }
        })
    }
</script>
