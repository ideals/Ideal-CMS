<?php
namespace Ideal\Field\Cid;

use Ideal\Field\AbstractController;
use Ideal\Core\Request;

class Controller extends AbstractController
{
    protected static $instance;


    public function showEdit()
    {
        $this->htmlName = $this->groupName . '_' . $this->name;
        $value = $this->getValue();

        if ($value == '') {
            $input = '<input type="hidden" id="' . $this->htmlName
                   . '" name="' . $this->htmlName
                   . '" value="' . $value . '">';
        } else {
            $cid = new Model($this->model->params['levels'], $this->model->params['digits']);
            $value = $cid->getBlock($value, $this->model->object['lvl']);

            $input = '<input type="text" class="input ' . $this->widthEditField
                . '" name="' . $this->htmlName
                . '" id="' . $this->htmlName
                .'" value="' . $value .'">';
            $label = $this->getLabelText();

            $input = <<<HTML
        <div id="{$this->htmlName}-control-group" class="control-group">
            <label class="control-label" for="{$this->htmlName}">{$label}</label>
            <div class="controls {$this->htmlName}-controls">
                {$input}
                <div id="{$this->htmlName}-help"></div>
            </div>
        </div>
HTML;
        }
        return $input;
    }


    public function getInputText()
    {
        // Заглушка для абстрактного метода
    }


    public function getValueForList($values, $fieldName)
    {
        $cid = new Model($this->model->params['levels'], $this->model->params['digits']);
        return $cid->getBlock($values['cid'], $values['lvl']);
    }


    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = $request->$fieldName;
        $model = $this->model;

        if ($this->newValue == '') {
            // Вычисляем новый ID
            $path = $model->getPath();
            $c = count($path);
            $end = $path[$c - 1];
            if ($c > 1 AND ($path[$c - 2]['structure'] != $end['structure'])) {
                $end['cid'] = '';
                $end['lvl'] = 0;
            }
            $this->newValue = $model->getNewCid($end['cid'], $end['lvl'] + 1);
        } else {
            $cid = new Model($model->params['levels'], $model->params['digits']);
            $obj = $model->object;
            if ($obj['cid'] != $request->$fieldName) {
                // Инициируем изменение cid, только если он действительно изменился
                $this->sqlAdd = $cid->moveCid($obj['cid'], $request->$fieldName, $obj['lvl']);
            }
            $this->newValue = $obj['cid']; // cid не меняем, т.к. все изменения будут через доп. запрос
        }

        return $this->newValue;
    }

}