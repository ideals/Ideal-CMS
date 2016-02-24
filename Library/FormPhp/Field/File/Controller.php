<?php

namespace FormPhp\Field\File;


use FormPhp\Field\AbstractField;

/**
 * Поле для загрузки нескольких файлов
 *
 */
class Controller extends AbstractField
{
    /**
     * Получение класса отправки формы
     *
     * @return string JS код класса
     */
    public function getSenderJs()
    {
        return <<<JS
            /**
             * Объект фрейм
             * копирование формы в фрейм
             * Отправка фрейма
             * Получение данных из фрейма
             * @type {{}}
             */
            senderAjax = {
                /**
                 * Отправка формы через iframe
                 * @param form formID ID формы
                 * @param options Свойства формы
                 * @param callback Функция, которую нужно будет вывзвать после отправки формы
                 */
                send: function(form, options, callback) {
                    this.formCallback = callback;
                    this.form = form;
                    this.formOptions = options;
                    if (typeof $(this).id == "undefined") {
                        this.create(options.ajaxUrl);
                    }
                    $(form).attr('target', this.id);
                    $(form).attr('action', options.ajaxUrl);
                    $(form).attr('method', 'post');
                    form.defaultSubmit = true;
                    $(form).submit();
                },
                /**
                 * Создание фрейма
                 * @param url URL на который будут переданы данные при отправки фрейма
                 * @returns {*|jQuery} Объект iframe
                 */
                create: function(url) {
                    var id = 'iFrameID' + Math.floor(Math.random() * 99999);
                    var html = '<iframe id="' + id + '" name="' + id + '" url="' + url
                        + '" src="about:blank" style="display: none;"></iframe>';
                    $(this.form).append(html);
                    this.iframe = $(this.form).children('iframe');
                    this.iframe.load(function() {
                        senderAjax.callback(url, senderAjax.getIFrameXML());
                    });
                    this.id = id;
                },
                /**
                 * Получение содержимого iframe после отправки
                 * @param e
                 * @returns {*}
                 */
                getIFrameXML: function(e) {
                    var doc = $(this.iframe).contents().find("body").html();
                    if (this.formOptions.ajaxDataType == 'json' || this.formOptions.ajaxDataType == 'jsonp') {
                         doc = $.parseJSON(doc);
                    }
                    return doc;
                },
                /**
                 *
                 * @param act
                 * @param doc
                 */
                callback: function (act, doc) {
                    $(this.iframe).remove();
                    this.form.defaultSubmit = false;
                    this.formCallback.apply(this.form, [doc])
                }
            };
JS;
    }
}
