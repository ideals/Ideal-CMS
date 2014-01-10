<?php
namespace Ideal\Field\Cid;

use Ideal\Field\AbstractController;
use Ideal\Core\Request;

class Controller extends AbstractController
{
    protected static $instance;


    public function showEdit()
    {
        $value = $this->getValue();

        if ($value == '') {
            $html = '<input type="hidden" id="' . $this->htmlName
                  . '" name="' . $this->htmlName
                  . '" value="' . $value . '">';
        } else {
            $html = parent::showEdit();
        }
        return $html;
    }


    public function getInputText()
    {
        $value = $this->getValue();

        $cid = new Model($this->model->params['levels'], $this->model->params['digits']);
        $pageData = $this->model->getPageData();
        $value = $cid->getBlock($value, $pageData['lvl']);

        $input = '<input type="text" class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            .'" value="' . $value .'">';

        return $input;
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
        /** @var \Ideal\Structure\Part\Admin\Model $model */
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
            $pageData = $this->model->getPageData();
            $obj = $pageData;
            if ($obj['cid'] != $request->$fieldName) {
                // Инициируем изменение cid, только если он действительно изменился
                $this->sqlAdd = $cid->moveCid($obj['cid'], $request->$fieldName, $obj['lvl']);
            }
            $this->newValue = $obj['cid']; // cid не меняем, т.к. все изменения будут через доп. запрос
        }

        return $this->newValue;
    }
}
