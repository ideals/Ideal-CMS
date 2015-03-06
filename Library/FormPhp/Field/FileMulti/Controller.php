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
    public function getSenderClassName()
    {
        return "IFrame";
    }
}
