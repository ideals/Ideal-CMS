<?php
namespace Ideal\Field\DateAuto;

use Ideal\Field\Date;

class Controller extends Date\Controller
{
    protected static $instance;


    public function getInputText()
    {
        $html = parent::getInputText();
        return $html;
    }


    public function getValue()
    {
        return time();
    }

}
