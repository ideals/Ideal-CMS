<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\SelectMulti;

/**
 * Class Controller
 *
 * @package Ideal\Field\SelectMulti
 */
class Controller extends \Ideal\Field\AbstractController
{
    /** @var  \Ideal\Medium\AbstractModel Объект доступа к редактируемым данным */
    protected $medium;

    /**
     * {@inheritdoc}
     */
    public function setModel($model, $fieldName, $groupName = 'general')
    {
        parent::setModel($model, $fieldName, $groupName);

        // Инициализируем медиума для получения список значений select и сохранения данных
        $className = $this->field['medium'];
        $this->medium = new $className($this->model, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $list = $this->medium->getList();
        $variants = $this->medium->getValues();
        $html = '<select multiple="multiple" class="form-control" name="' . $this->htmlName
            . '[]" id="' . $this->htmlName . '">';
        foreach ($list as $k => $v) {
            $selected = '';
            if (in_array($k, $variants)) {
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
    public function parseInputValue($isCreate)
    {
        // Если товар связан с категорией через промежуточную таблицу

        $this->newValue = null;
        $newValue = $this->pickupNewValue();

        $item = array(
            'fieldName' => $this->htmlName,
            'value' => null,
            'message' => '',
            'sqlAdd' => $this->medium->getSqlAdd($newValue)
        );

        return $item;
    }
}
