<?php
namespace Ideal\Field\Pos;

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


    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = intval($request->$fieldName);
        $model = $this->model;

        $newPos = $this->newValue;
        $pageData = $this->model->getPageData();
        $oldPos = (isset($pageData['pos'])) ? $pageData['pos'] : 0;

        // Если был указан и не изменился, то оставляем как есть
        // Если был указан и изменился, перенумеровываем список
        if ($this->newValue == '') {
            // Если pos не был указан, надо поставить максимальный
            $this->newValue = $model->getNewPos();
        } elseif ($oldPos != $newPos) {
            $posModel = new Model();
            $this->sqlAdd = $posModel->movePos($oldPos, $newPos, $model->getPrevStructure());
            $this->newValue = $oldPos; // возвращаем старое значение, т.к. все перестановки идут в movePos
        }

        return $this->newValue;
    }

}