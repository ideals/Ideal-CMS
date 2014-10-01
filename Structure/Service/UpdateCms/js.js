/** Получение новых версий и создания представления */
$.jsonp({
    url: urlSrv,
    callbackParameter: 'callback',
    dataType: 'jsonp',
    success: function (versions) {
        var nowVersions = nowVersions;
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
        url: 'index.php?par=' + url,
        type: 'POST',
        dataType: 'json',
        data: {
            mode: 'ajax',
            controller: '\\Ideal\\Structure\\Service\\UpdateCms',
            action: 'ajaxDownload',
            name: moduleName,
            version: version
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
