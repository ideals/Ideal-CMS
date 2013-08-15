<?php
namespace Ideal\Field\Hidden;

use Ideal\Field\AbstractController;

class Controller extends AbstractController
{
    protected static $instance;


    public function showEdit()
    {
        $this->htmlName = $this->groupName . '_' . $this->name;
        $input = $this->getInputText();
        return $input;
    }


    public function getInputText()
    {
        return '<input type="hidden" id="' . $this->htmlName
            . '" name="' . $this->htmlName
            . '" value="' . $this->getValue() . '">';
    }

}