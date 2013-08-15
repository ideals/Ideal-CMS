<?php
namespace Ideal\Field\Password;

use Ideal\Field\AbstractController;

use Ideal\Core\Request;

class Controller extends AbstractController
{
    protected static $instance;
    public $newCheckValue;


    public function getInputText()
    {
        return '<script type="text/javascript" src="Ideal/Field/Password/admin.js" />'
            .'<input type="password" id="' . $this->htmlName
            . '" name="' . $this->htmlName
            . '" >'
            .'&nbsp;'. '<i id="'.$this->htmlName.'-ico'.'">'.'</i>'
            .'&nbsp; <input type="password" id="' . $this->htmlName.'-check'
            . '" name="' . $this->htmlName.'-check'
            . '" >';
    }




    public function parseInputValue($isCreate)
    {
        $this->newValue = $this->pickupNewValue();


        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name.'-check';
        $this->newCheckValue = $request->$fieldName;


        $item = array();
        $item['fieldName'] = $this->htmlName;

        if ($this->newValue == '') {
            $item['value'] = NULL;
        } else {
            $item['value'] = crypt($this->newValue);
        }

        $item['message'] = '';

       if ($this->newValue !== $this->newCheckValue) {
            $item['message'] = 'пароли не совпадают';
        }

        if (empty($this->newValue) AND $isCreate) {
            // При создании элемента поле с паролем всегда должно быть заполнено
            $item['message'] = 'необходимо заполнить это поле';
        }

        return $item;
    }

}