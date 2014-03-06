<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Integer;

use Ideal\Field\AbstractController;

/**
 * Класс поля, которое может содержать только числовое значение типа Integer
 */
class Controller extends AbstractController
{
    /** @var  mixed Хранит в себе копию соответствующего объекта поля (паттерн singleton) */
    protected static $instance;

    /**
     * Возвращает строку, содержащую html-код элементов ввода для редактирования поля
     *
     * @return string html-код элементов ввода
     */
    public function getInputText()
    {
        $value = intval($this->getValue());
        return '<input type="text" class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '" value="' . $value . '">';
    }

    /**
     * Определение значения этого поля на основании данных из модели
     *
     * В случае, если в модели ещё нет данных, то значение берётся из поля default
     * в настройках структуры (fields) для соответствующего поля
     *
     * @return string
     */
    public function getValue()
    {
        $value = intval(parent::getValue());
        return $value;
    }
}
