<?php
namespace Ideal\Field;

use Ideal\Core\Request;

abstract class AbstractController
{
    protected $name;
    protected $label;
    protected $widthEditField = 'span7';
    protected $groupName;
    protected $model;
    protected $sqlAdd = '';
    public $newValue;
    public $htmlName;
    protected static $instance;


    public static function getInstance()
    {
        // PHP53 Late static binding
        if (empty(static::$instance)) {
            $className = get_called_class();
            static::$instance = new $className();
        }
        return static::$instance;
    }


    public function getValue()
    {
        // TODO определение значения по умолчанию из $this->model->params
        // TODO сделать определение значения по умолчанию, если для этого указан геттер и убрать этот функционал из selectField
        $value = '';
        $pageData = $this->model->getPageData();
        if (isset($pageData)) {
            if (isset($pageData[$this->name])) {
                $value = $pageData[$this->name];
            }
        }
        return $value;
    }


    public function showEdit()
    {
        $label = $this->getLabelText();
        $input = $this->getInputText();
        $html = <<<HTML
        <div id="{$this->htmlName}-control-group" class="control-group">
            <label class="control-label" for="{$this->htmlName}">{$label}</label>
            <div class="controls {$this->htmlName}-controls">
                {$input}
                <div id="{$this->htmlName}-help"></div>
            </div>
        </div>
HTML;

        return $html;
    }


    public function getLabelText()
    {
        return $this->label . ':';
    }


    public function setModel($model, $fieldName, $groupName = 'general')
    {
        $this->name = $fieldName;
        $this->model = $model;
        $this->field = $model->fields[$fieldName];
        $this->label = $this->field['label'];
        $this->groupName = $groupName;
        $this->htmlName = $this->groupName . '_' . $this->name;
    }


    abstract public function getInputText();


    public function getValueForList($values, $fieldName)
    {
        return $values[$fieldName];
    }


    public function parseInputValue($isCreate)
    {
        $this->newValue = $this->pickupNewValue();

        $item = array(
            'fieldName' => $this->htmlName,
            'value' => $this->newValue,
            'message' => '',
            'sqlAdd' => ''
         );

        // В первой версии только на правильность данных и их наличие, если в описании бд указано not null
        if (($this->name == 'ID') AND $isCreate) {
            if (!empty($this->newValue)) {
                $item['message'] = 'При создании элемента поле ID не может быть заполнено';
            }
            return $item;
        }

        if (($this->name == 'ID') AND empty($this->newValue)) {
            // Если это поле ID и оно пустое, значит элемент создаётся - не нужно на нём ошибку отображать
            return $item;
        }

        $sql = strtolower($this->field['sql']);
        if (empty($this->newValue)
                AND (strpos($sql, 'not null') !== false)
                AND (strpos($sql, 'default') === false)) {
            // Установлен NOT NULL и нет DEFAULT и $value пустое
            $item['message'] = 'необходимо заполнить это поле';
        }

        $item['sqlAdd'] = $this->sqlAdd;

        return $item;
    }


    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = $request->$fieldName;
        return $this->newValue;
    }

}