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
    public function getFileInput()
    {
        $xhtml = ($this->xhtml) ? '/' : '';
        return '<input class="'
        . $this->options['id']
        . '" type="file" name="file" value="" '
        . $xhtml . '>' . "\n";
    }

    /**
     * Получение кнопки добавления поля отправки файла
     *
     * @return string Поле отправки файла
     */
    public function getAddFileButton()
    {
        $xhtml = ($this->xhtml) ? '/' : '';
        return '<input type="button" value="add file" class="multiFileAddButton" data-input-id='
        . $this->options['id']
        . '  name="file" value="" ' . $xhtml . '>' . "\n";
    }

    /**
     * Получение js кода, необходимого для работы поля
     */
    public function getJs()
    {
        return <<<JS
$(".multiFileAddButton").click(function() {
    var baseInput = $('.' + $(this).data('input-id'));
    var fileInput = baseInput.clone();
    baseInput.after(fileInput);
});
JS;

    }
}
