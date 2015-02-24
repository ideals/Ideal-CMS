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
        return '<input id="base-file-input-'
        . $this->formName
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
        return '<input type="file" name="file" value="" ' . $xhtml . '>' . "\n";
    }

    /**
     * Получение js кода, необходимого для работы поля
     */
    public function getJs()
    {
        return <<<JS

JS;

    }
}
