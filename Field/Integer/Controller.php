<?php
namespace Ideal\Field\Integer;

use Ideal\Field\AbstractController;
use Ideal\Core\Request;

class Controller extends AbstractController
{
    protected static $instance;

    public function getInputText()
    {
        $value = htmlspecialchars($this->getValue());
        return '<input type="text" class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '" value="' . $value . '">';
    }

    public function getValue()
    {
        $value = 0;
        $pageData = $this->model->getPageData();
        if (isset($pageData[$this->name]) && is_int($pageData[$this->name])) {
            $value = $pageData[$this->name];
        } elseif (isset($this->field['default'])) {
            $value = $this->field['default'];
        } else {
            $value = 0;
        }
        return $value;
    }

    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = (int)$request->$fieldName;
        return $this->newValue;
    }

}
