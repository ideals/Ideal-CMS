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
     * Получение js кода, необходимого для работы поля
     */
    public function getJs()
    {
        return <<<JS
            $('.file-input-block')
                .children('[type=button]')
                .addClass('multi-file-add-button')
                .attr('data-block', "file-input-block-{$this->options['id']}");
            $('.file-input-block').children('.base-input').children('input').attr('name', 'base-file');
            $(".multi-file-add-button").click(function() {
                var fileBlock = '#' + $(this).data('block');
                var name = 'file' + Math.floor(Math.random() * 99999);
                $(fileBlock).children('.base-input').children('input').attr('name', name);
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
        senderAjax = {
            /**
             * Отправка формы через iframe
             * @param form formID ID формы
             * @param url URL на который будут переданы данные при отправки фрейма
             * @param callback Функция, которую нужно будет вывзвать после отправки формы
             */
            send: function(form, url, callback) {
                var http_request = this.getXMLHttpReques();

                var data = $(form).serialize();
                var file = $(form).children('[type=file]')

                var name = file.fileName || file.name;

                // Обработчик прогресса загрузки
                // Полный размер файла - event.total, загружено - event.loaded
                http_request.upload.addEventListener('progress', function (event) {
                    var percent = Math.ceil(event.loaded / event.total * 100);
                    methods.ajaxFileProgress.apply(this, [percent]);
                }, false);

                // Отправить файл на загрузку
                http_request.open('POST', url + '?fname=' + name, true);
                http_request.setRequestHeader('Referer', location.href);
                http_request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                http_request.setRequestHeader('X-File-Name', encodeURIComponent(name));
                http_request.setRequestHeader('Content-Type', 'application/octet-stream');
                http_request.onreadystatechange = function () {
                    if (http_request.readyState == 4) {
                        if (http_request.status == 200) {
                            methods.ajaxFileProgress.apply(this, [100]);
                            // methods.ajaxFileFinish.apply(this, );
                            return true;
                        } else {
                            // Ошибка загрузки файла
                            return false;
                        }
                    }
                };
                http_request.send(file);
            },
            getXMLHttpReques: function() {
                // Mozilla, Safari, Opera, Chrome
                if (window.XMLHttpRequest) {
                    var http_request = new XMLHttpRequest();
                } else if (window.ActiveXObject) {
                    // Internet Explorer
                    try {
                        http_request = new ActiveXObject('Msxml2.XMLHTTP');
                    } catch (e) {
                        try {
                            http_request = new ActiveXObject('Microsoft.XMLHTTP');
                        } catch (e) {
                            // Браузер не поддерживает эту технологию
                            return false;
                        }
                    }
                } else {
                    // Браузер не поддерживает эту технологию
                    return false;
                }
                return http_request;
            }
        }
JS;
    }
}
