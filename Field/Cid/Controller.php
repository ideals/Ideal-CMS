<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Cid;

use Ideal\Core\Request;
use Ideal\Field\AbstractController;

/**
 * Cid — поле используемое для сортировки элементов на своём уровне
 *
 * Поле cid должно использоваться в паре с полем lvl, определяющим на каком уровне
 * вложенности находится этот элемент.
 * Пример объявления в конфигурационном файле структуры:
 *     'cid' => array(
 *         'label' => '№',
 *         'sql'   => 'char(' . (6 * 3) . ') not null',
 *         'type'  => 'Ideal_Cid'
 *     ),
 * При определении размера поля учитывается количество уровней вложенности (в примере это 6) и количество разрядов
 * на каждом уровне (в примере это 3, т.е. на каждом уровне может быть до 999 элементов)
 */
class Controller extends AbstractController
{

    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $value = $this->getValue();

        $cid = new Model($this->model->params['levels'], $this->model->params['digits']);
        $pageData = $this->model->getPageData();
        $value = $cid->getBlock($value, $pageData['lvl']);

        $input = '<input type="text" class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '" value="' . $value . '">';

        return $input;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForList($values, $fieldName)
    {
        $cid = new Model($this->model->params['levels'], $this->model->params['digits']);
        return $cid->getBlock($values['cid'], $values['lvl']);
    }

    /**
     * {@inheritdoc}
     */
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
            if ($c > 1 && ($path[$c - 2]['structure'] != $end['structure'])) {
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

    /**
     * {@inheritdoc}
     */
    public function showEdit()
    {
        $value = $this->getValue();

        if ($value == '') {
            // При создании элемента cid нельзя указать, он прописывается автоматически в конец списка
            $html = '<input type="hidden" id="' . $this->htmlName
                . '" name="' . $this->htmlName
                . '" value="' . $value . '">';
        } else {
            $html = parent::showEdit();
        }
        return $html;
    }
}
