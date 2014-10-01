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
                buf = 'updateModule("' + key + '","' + line['version'] + '")';
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
function Update(moduleName, version, url, modalBox) {
    this.url = 'index.php?par=' + url;
    this.modalBox = modalBox;
    this.ajaxData = Object.freeze({
        mode: 'ajax',
        controller: '\\Ideal\\Structure\\Service\\UpdateCms',
        name: moduleName,
        version: version
    });

    this.ajaxRequest = function(data) {
        var update = this;
        $.ajax({
            url: this.url,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function (result) {
                var check = update.dataCheck(result);
                if (check) {
                    update.print('Успешно выполнено действие ' + data.action, 'success');
                    update.run(result, data.action);
                }
            },
            error: function () {
                update.print('Не удалось выполнить ajax запрос', 'error');
            }
        })
    };

    /**
     * Проверка данных полученных из ajax запроса
     * @param data
     * @returns {boolean}
     */
    this.dataCheck = function(data) {
        // Если не получен структурированный результат выполнения действия и метод вызван не в первый раз,
        // выводим полученные данные
        if (data.error == 'undefined' && data != true ) {
            this.print('Сбой в работе при выполнении действия' + action + '<br />' + data, 'error');
            return false;
        }
        // Если получено сообщение, но ошибка не зафиксирована
        if ((data.message != null) && (data.error == false)) {
            this.print(data.message, 'message');
        }
        // Если получено сообщение и зафиксирована ошибка
        if ((data.message != null) && (data.error == true)) {
            this.print(data.message, 'error');
            return false;
        }
        // Если нет сообщения, но зафиксирована ошибка
        if ((data.message == null) && (data.error == true)) {
            this.print('Произошла ошибка в работе метода ' + action, 'error');
            return false;
        }
        return true;
    };

    this.print = function(data, type) {
        type = type || 'info';
        var classBlock = '';
        switch (type) {
            case ('error'): classBlock = 'alert alert-danger fade in'; break;
            case ('info'): classBlock = 'alert alert-info fade in'; break;
            case ('success'): classBlock = 'alert alert-success fade in'; break;
            case ('warning'): classBlock = 'alert alert-warning fade in'; break;
            default: classBlock = 'bg-info';
        }
        $('<div>').appendTo(this.modalBox.find('.modal-body')).html(data).attr('class', classBlock);
    };

    this.run = function(result, action) {
        // Выполненное действие
        action = action || null;

        var data = {};
        $.extend(data, this.ajaxData);
        var scriptsExecutes = 0;
        switch (action) {
            case null:
                data.action = 'ajaxDownload';
                break;
            case 'ajaxDownload':
                data.action = 'ajaxUnpack';
                break;
            case 'ajaxUnpack':
                data.action = 'ajaxSwap';
                break;
            case 'ajaxSwap':
                data.action = 'ajaxGetUpdateScript';
                break;
            case 'ajaxRunScript':
            case 'ajaxGetUpdateScript':
                if (result.data.count != 'undefined') {
                    scriptsExecutes = result.data.count;
                }
                if (scriptsExecutes > 0) {
                    data.action = 'ajaxRunScript';
                    scriptsExecutes = scriptsExecutes--;
                } else {
                    data.action = 'ajaxFinish';
                }
                break;
            case 'ajaxFinish':
                return true;
            default:
                return false;
        }
        this.ajaxRequest(data);

    };
}

/** Обновление CMS или модуля */
function updateModule(moduleName, version) {
    var update = new Update(moduleName, version, url, $('#modalUpdate'));
    update.modalBox.find('.modal-body').html();
    // Открываем модальное окно
    update.modalBox.modal('show');
    update.print('Обновление начато!', 'success');
    update.run(true);
    update.modalBox.find('.close, .btn-close').removeAttr('disabled');
}