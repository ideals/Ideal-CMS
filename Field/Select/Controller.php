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
        $className = $this->field['medium'] . '\\Model';
        $medium = new $className();
        $medium->setObj($this->model);
        $medium->setFieldName($fieldName);
        $this->list = $medium->getList();
    }


    public function getInputText()
    {
        $html = '<select class="form-control" name="' . $this->htmlName . '" id="' . $this->htmlName . '">';
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
            // Если значение не указано, то будет выбран первый элемент из списка
            $keys = array_keys($this->list);
            $value = $keys[0];
        }
        return $value;
    }

}