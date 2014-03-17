<?php
namespace Ideal\Field\SelectMulti;

use Ideal\Field\AbstractController;
use Ideal\Core\Request;

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
        $html = '<div class="col-xs-12" style="max-height:120px; overflow-y: scroll; border: 1px solid #C0C0C0;border-radius: 5px;"'
                .' name="' . $this->htmlName .'" id="' . $this->htmlName .'">';
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


    function getValue()
    {
        $value = parent::getValue();
        $value = explode(',', $value);
        return $value;
    }


    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = $request->$fieldName;
        $this->newValue = implode(',', $this->newValue);
        return $this->newValue;
    }

}