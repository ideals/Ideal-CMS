<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Select;

use Ideal\Field\AbstractController;

/**
 * Class Controller
 *
 * @package Ideal\Field\Select
 */
class Controller extends AbstractController
{
    protected $list; // Опции списка
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function setModel($model, $fieldName, $groupName = 'general')
    {
        parent::setModel($model, $fieldName, $groupName);

        if (isset($this->field['values'])) {
            // Если значения select заданы с помощью массива в поле values
            $this->list = $this->field['values'];
            return;
        }

        // Загоняем в $this->list список значений select
        $className = $this->field['medium'];
        /** @var \Ideal\Medium\AbstractModel $medium */
        $medium = new $className($this->model, $fieldName);
        $this->list = $medium->getList();
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $html = '<select class="form-control" name="' . $this->htmlName . '" id="' . $this->htmlName . '">';
        $value = $this->getValue();
        foreach ($this->list as $k => $v) {
            $selected = '';
            if ($k == $value) {
                $selected = ' selected="selected"';
            }
            $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $value = parent::getValue();
        if ($value == '') {
            // Если значение не указано, то будет выбран первый элемент из списка
            $keys = array_keys($this->list);
            $value = $keys[0];
        }
        return $value;
    }
}
