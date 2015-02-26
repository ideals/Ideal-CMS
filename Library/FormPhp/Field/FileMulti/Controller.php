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
        if (isset($this->options['inputHtml'])) {
            return $this->options['inputHtml'];
        }

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
        $inputHtml = $this->getFileInput();
        $xhtml = ($this->xhtml) ? '/' : '';
        return '<input type="button" value="add file" class="multiFileAddButton" data-input-id='
        . $this->options['id']
        . '  name="file" value="" ' . $xhtml . '>' . "\n"
        . "<div class='base-file-input-" . $this->options['id'] . "' style='display: none'>{$inputHtml}</div>";
    }

    /**
     * Получение js кода, необходимого для работы поля
     */
    public function getJs()
    {
        return <<<JS
$('.base-file-input-{$this->options['id']}').children('input').removeClass("{$this->options['id']}");
$(".multiFileAddButton").click(function() {
    var baseHtml = $('.base-file-input-' + $(this).data('input-id')).html();
    var fileInput = $('.' + $(this).data('input-id'));
    fileInput.after(baseHtml);
});
JS;

    }
}
