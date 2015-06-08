/** Получение новых версий и создания представления */
$.jsonp({
    url: urlSrv,
    callbackParameter: 'callback',
    dataType: 'jsonp',
    success: function (versions) {
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
                buf = 'updateModule("' + key + '", "' + line['version'] + '", "' + value + '")';
                $('<button>')
                    .appendTo('form:last')
                    .attr('class', 'btn ')
                    .attr('onClick', buf)
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

/**
 * Объект осуществляющий процесс обновления*/
function Update(moduleName, version, currentVersion, url, modalBox) {
    this.url = 'index.php?par=' + url;
    this.modalBox = modalBox;
    this.ajaxData = Object.freeze({
        mode: 'ajax',
        controller: '\\Ideal\\Structure\\Service\\UpdateCms',
        name: moduleName,
        version: version,
        currentVersion: currentVersion
    });

    this.ajaxRequest = function(data) {
        var update = this;
        $.ajax({
            url: this.url,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function (result) {
                var check = update.dataCheck(result, data.action);
                if (check) {
                    update.run(result, data.action);
                } else {
                    update.modalBox.find('.close, .btn-close').removeAttr('disabled');
                }
            },
            error: function (result) {
                update.print('Не удалось выполнить ajax запрос: ' + data.action + '<br />' + result.responseText, 'error');
                update.modalBox.find('.close, .btn-close').removeAttr('disabled');
            }
        })
    };

    /**
     * Проверка данных полученных из ajax запроса
     * @param data
     * @returns {boolean}
     */
    this.dataCheck = function(data, action) {
        // Если не получен структурированный результат выполнения действия и метод вызван не в первый раз,
        // выводим полученные данные
        if (data.error == 'undefined' && data != true ) {
            this.print('Сбой в работе при выполнении действия' + action + '<br />' + data, 'error');
            return false;
        }
        // Выводим полученные сообщения
        for (var i = 0; i < data.message.length; i++) {
            this.print(data.message[i][0], data.message[i][1]);
        }
        // Если нет сообщений, но зафиксирована ошибка
        if ((data.message.length == 0) && (data.error == true)) {
            this.print('Произошла ошибка в работе метода ' + action, 'error');
            return false;
        }
        return data.error === false;
    };

    this.print = function(data, type) {
        type = type || 'info';
        var classBlock = '';
        switch (type) {
            case ('error'): classBlock = 'alert alert-danger fade in'; break;
            case ('info'): classBlock = 'alert alert-info fade in'; break;
            case ('success'): classBlock = 'alert alert-success fade in'; break;
            case ('warning'): classBlock = 'alert alert-warning fade in'; break;
            default: classBlock = 'alert alert-info fade in';
        }
        $('<div>').appendTo(this.modalBox.find('.modal-body')).html(data).attr('class', classBlock);
    };

    this.run = function(result, action) {
        // Выполненное действие
        action = action || null;
        var data = {};
        $.extend(data, this.ajaxData);
        switch (action) {
            case null:
                data.action = 'ajaxDownload';
                break;
            case 'ajaxDownload':
                data.action = 'ajaxUnpack';
                break;
            case 'ajaxUnpack':
                data.action = 'ajaxGetUpdateScript';
                break;
            case 'ajaxGetUpdateScript':
                this.phpScripts = JSON.parse(result.data.scripts);
                if (typeof(this.phpScripts['pre']) != 'undefined') {
                    this.phpScripts['pre']['count'] = this.phpScripts['pre'].length;
                } else {
                    this.phpScripts['pre'] = {};
                    this.phpScripts['pre']['count'] = 0;
                }
                if (typeof(this.phpScripts['after']) != 'undefined') {
                    this.phpScripts['after']['count'] = this.phpScripts['after'].length;
                } else {
                    this.phpScripts['after'] = {};
                    this.phpScripts['after']['count'] = 0;
                }
                if (this.phpScripts['pre']['count'] > 0) {
                    this.actionScript = this.phpScripts['pre'];
                    this.actionScriptsequence = 'pre';
                    this.actionScript['count']--;
                    data.action = 'ajaxRunScript';
                    break;
                }
                data.action = 'ajaxSwap';
                break;
            case 'ajaxSwap':
                if (this.phpScripts['after']['count'] > 0) {
                    this.actionScript = this.phpScripts['after'];
                    this.actionScriptsequence = 'after';
                    this.actionScript['count']--;
                    data.action = 'ajaxRunScript';
                    break;
                }
                data.action = 'ajaxEndVersion';
                break;
            case 'ajaxRunScript':
                if (result.data != null) {
                    break;
                }
                if (this.actionScript['count'] > 0) {
                    data.action = 'ajaxRunScript';
                    this.actionScript['count']--;
                    break;
                } else if(this.actionScriptsequence == 'pre') {
                    data.action = 'ajaxSwap';
                    break
                }
                data.action = 'ajaxEndVersion';
                break;
            case 'ajaxEndVersion':
                if (result.data != null && result.data.next == 'true') {
                    data.action = 'ajaxDownload';
                    data.currentVersion = result.data.currentVersion;
                } else {
                    data.action = 'ajaxFinish';
                }
                break;
            case 'ajaxFinish':
                this.modalBox.find('.close, .btn-close').removeAttr('disabled');
                return true;
            default:
                this.modalBox.find('.close, .btn-close').removeAttr('disabled');
                return false;
        }
        this.ajaxRequest(data);

    };
}

/** Обновление CMS или модуля */
function updateModule(moduleName, version, currentVersion) {
    var update = new Update(moduleName, version, currentVersion, url, $('#modalUpdate'));
    update.modalBox.find('.modal-body').html('');
    update.modalBox.find('.close, .btn-close').attr('disabled', 'disabled');
    // Открываем модальное окно
    update.modalBox.modal('show');
    update.print('Обновление начато!', 'success');

    $('#modalUpdate').on('hidden.bs.modal', function (e) {
        location.reload(true);
    });

    var result = update.run(true);
}
