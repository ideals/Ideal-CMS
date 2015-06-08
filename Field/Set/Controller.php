<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Set;

use Ideal\Core\Request;
use Ideal\Field\AbstractController;

/**
 * Визуальный вывод и сохранение данных MySQL типа SET
 *
 * Поле Ideal_Set можно использовать для множественного выбора значений из небольшого набора
 * данных. Например, информация о размерах, вариантах исполнения и т.п.
 *
 * Пример объявления в конфигурационном файле структуры:
 *
 *     'size' => array(
 *         'label' => 'Размер',
 *         'sql'   => "set('XS','S','M','L','XL','XXL','XXXL','4XL','5XL')",
 *         'type'  => 'Ideal_Set',
 *         'values' => array('XS','S','M','L','XL','XXL','XXXL','4XL','5XL')
 *     ),
 *
 * todo сделать получение значений из поля sql, вместо поля values
 *
 */
class Controller extends AbstractController
{

    /** @inheritdoc */
    protected static $instance;

    /** @var array Список вариантов выбора для select */
    protected $list;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $html = '<div class="col-xs-12"'
            . ' style="max-height:120px; overflow-y: scroll; border: 1px solid #C0C0C0; border-radius: 5px;"'
            . ' name="' . $this->htmlName . '" id="' . $this->htmlName . '">';
        $value = $this->getValue();
        foreach ($this->list as $v) {
            $checked = '';
            if (array_search($v, $value) !== false) {
                $checked = ' checked="checked"';
            }
            $html .= '<label class="checkbox"><input type="checkbox" value="' . $v . '" '
                . $checked . ' name="' . $this->htmlName . '[]">' . $v . '</label>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $value = parent::getValue();
        $value = explode(',', $value);
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = $request->$fieldName;
        $this->newValue = implode(',', $this->newValue);
        return $this->newValue;
    }

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
        $medium = new $className();
        $this->list = $medium->getList($this->model, $fieldName);
    }
}
