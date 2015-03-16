<?php

namespace FormPhp\Field\FileMulti;


use FormPhp\Field\AbstractField;

/**
 * Поле для загрузки нескольких файлов
 *
 */
class Controller extends AbstractField
{
    /**
     * Получение поля отправки файла
     *
     * @return string Поле отправки файла
     */
    public function getFileInputBlock()
    {
        $input = $this->getFileInput();
        $button = $this->getAddFileButton();
        return <<<HTML
    <div class="file-input-block" id="file-input-block-{$this->options['id']}">
        <div class="inputs-block">
            {$input}
        </div>
        <div class="base-input" style="display: none;">
            {$input}
        </div>
        {$button}
    </div>
HTML;

    }

    /**
     * Получение поля отправки файла
     *
     * @return string Поле отправки файла
     */
    protected function getFileInput()
    {
        if (isset($this->options['inputHtml'])) {
            return $this->options['inputHtml'];
        }

        $xhtml = ($this->xhtml) ? '/' : '';
        return <<<HTML
            <input type="file" name="file" value="" {$xhtml}>
HTML;
    }

    /**
     * Получение кнопки добавления поля отправки файла
     *
     * @return string Поле отправки файла
     */
    protected function getAddFileButton()
    {
        if (isset($this->options['buttonHtml'])) {
            return $this->options['buttonHtml'];
        }

        $xhtml = ($this->xhtml) ? '/' : '';
        return <<<HTML
        <input type="button" value="add file" $xhtml>
HTML;
    }

    /**
     * Получение js кода, необходимого для работы поля
     */
    public function getJs()
    {
        return <<<JS
            $('.file-input-block')
                .children('[type=button]')
                .addClass('multi-file-add-button')
                .attr('data-block', "file-input-block-{$this->options['id']}");
                $('.file-input-block').children('.base-input').children('input').attr('name', 'base-file')
            $(".multi-file-add-button").click(function() {
                var fileBlock = '#' + $(this).data('block');
                var name = 'file' + Math.floor(Math.random() * 99999);
                $(fileBlock).children('.base-input').children('input').attr('name', name)
                var baseInput = $(fileBlock).children('.base-input').html();
                $(fileBlock).children('.inputs-block').append(baseInput);
                $(fileBlock).children('.base-input').children('input').attr('name', 'base-file')
            });
JS;

    }

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
                 * @param url URL на который будут переданы данные при отправки фрейма
                 * @param callback Функция, которую нужно будет вывзвать после отправки формы
                 */
                send: function(form, url, callback) {
                                    this.formCallback = callback;
                    this.form = form;
                    if (typeof $(this).id == "undefined") {
                        this.create(url);
                    }
                    $(form).attr('target', this.id);
                    $(form).attr('action', url);
                    $(form).attr('method', 'post');
                    $(form).unbind('submit');
                    /*$(form).submit(function() {
                        return true;
                    });*/
                    $(form).submit();
                    return false;
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
                    var doc = this.iframe.contentDocument;
                    if (!doc &&this.iframe.contentWindow) doc = this.iframe.contentWindow.document;
                    if (!doc) doc = window.frames[this.id].document;
                    if (!doc) return null;
                    if (doc.location == "about:blank") return null;
                    if (doc.XMLDocument) doc = doc.XMLDocument;
                    return doc;
                },
                /**
                 *
                 * @param act
                 * @param doc
                 */
                callback: function (act, doc) {
                    $(this.iframe).remove();
                    this.formCallback.apply(this.form, [doc.body.innerHTML])
                }
            };
JS;
    }
}
