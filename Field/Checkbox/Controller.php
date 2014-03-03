<?php
namespace Ideal\Field\Checkbox;

use Ideal\Field\AbstractController;
use Ideal\Core\Request;

class Controller extends AbstractController
{
    protected static $instance;


    public function getLabelText()
    {
        return '';
    }


    public function getInputText()
    {
        $checked = ($this->getValue() == 1) ? 'checked="checked"' : '';
        return '<label class="checkbox"><input type="checkbox" name="' . $this->htmlName
            . '" id="' . $this->htmlName . '" '
            . $checked .'> '. $this->field['label'] . '</label>';
    }


    public function getValueForList($values, $fieldName)
    {
        return ($values[$fieldName] == 1) ? 'Да' : 'Нет';
    }


    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = ($request->$fieldName == '') ? 0 : 1;
        return $this->newValue;
    }

}