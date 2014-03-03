<?php
namespace Ideal\Field\Text;

use Ideal\Field\AbstractController;

class Controller extends AbstractController
{
    protected static $instance;

    public function getInputText()
    {
        $value = htmlspecialchars($this->getValue());
        return '<input type="text" class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            .'" value="' . $value .'">';
    }

}