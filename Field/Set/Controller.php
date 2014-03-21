<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Field\Set;

use Ideal\Field\AbstractController;
use Ideal\Core\Request;

/**
 * Визуальный вывод и сохранение данных MySQL типа SET
 * Class Controller
 * @package Ideal\Field\Set
 */
class Controller extends AbstractController
{
    protected $list; // Опции списка

    protected static $instance;

    /**
     * Установка модели редактируемого объекта, частью которого является редактируемое поле
     *
     * Полю необходимо получать сведения о состоянии объекта и о других полях, т.к.
     * его значения и поведение может зависеть от значений других полей
     *
     * @param \Ideal\Core\Admin\Model $model Модель редактируемого объекта
     * @param string $fieldName Редактируемое поле
     * @param string $groupName Вкладка, к которой принадлежит редактируемое поле
     */
    public function setModel($model, $fieldName, $groupName = 'general')
    {
        parent::setModel($model, $fieldName, $groupName);

        if (isset($this->field['values'])) {
            // Если значения селекта заданы с помощью массива в поле values
            $this->list = $this->field['values'];
            return;
        }

        // Загоняем в $this->list список значений select
        $className = $this->field['class'];
        $getter = new $className();
        $this->list = $getter->getList($this->model, $fieldName);
    }

    /**
     * Возвращает строку, содержащую html-код элементов ввода для редактирования поля
     *
     * @return string html-код элементов ввода
     */
    public function getInputText()
    {
        $html = '<div class="col-xs-12" style="max-height:120px; overflow-y: scroll; border: 1px solid #C0C0C0;border-radius: 5px;"'
            . ' name="' . $this->htmlName . '" id="' . $this->htmlName . '">';
        $value = $this->getValue();
        foreach ($this->list as $v) {
            $checked = '';
            if (array_search($v, $value) !== false) {
                $checked = ' checked="checked"';
            }
            $html .= '<label class="checkbox"><input type="checkbox" value="' . $v . '" '
                . $checked . 'name="' . $this->htmlName . '[]">' . $v . '</label>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Определение значения этого поля на основании данных из модели
     *
     * В случае, если в модели ещё нет данных, то значение берётся из поля default
     * в настройках структуры (fields) для соответствующего поля
     *
     * @return string
     */
    function getValue()
    {
        $value = parent::getValue();
        $value = explode(',', $value);
        return $value;
    }

    /**
     * Получение нового значения поля из данных, введённых пользователем
     *
     * @return string
     */
    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = $request->$fieldName;
        $this->newValue = implode(',', $this->newValue);
        return $this->newValue;
    }

}
