<?php
namespace Ideal\Field\Area;

use Ideal\Field\AbstractController;

class Controller extends AbstractController
{
    protected static $instance;

    public function getInputText()
    {
        return '<textarea class="form-control" name="' . $this->htmlName
             . '" id="' . $this->htmlName
             .'">' . htmlspecialchars($this->getValue()) . '</textarea>';
    }
}
