<?php
namespace Ideal\Field\Select;

use Ideal\Field\AbstractController;

class Controller extends AbstractController
{
    protected $list; // Опции списка
    protected static $instance;


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


    public function getInputText()
    {
        $html = '<select class="' . $this->widthEditField . '" name="' . $this->htmlName .'" id="' . $this->htmlName .'">';
        $value = $this->getValue();
        foreach ($this->list as $k => $v) {
            $selected = '';
            if ($k == $value) {
                $selected = ' selected="selected"';
            }
            $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
        }
        $html .= '</select>';
        return $html;
    }


    function getValue()
    {
        $value = parent::getValue();
        if ($value == '') {
            // TODO если указано значение по умолчанию, возвращать его, а не первый элемент
            $keys = array_keys($this->list);
            $value = $keys[0];
        }
        return $value;
    }

}