<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Checkbox;

use Ideal\Core\Request;
use Ideal\Field\AbstractController;

/**
 * Отображение и редактирование поля, содержащего true/false (checkbox)
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'is_active' => array(
 *         'label' => 'Отображать на сайте',
 *         'sql'   => 'bool',
 *         'type'  => 'Ideal_Checkbox'
 *     ),
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
        $checked = ($this->getValue() == 1) ? 'checked="checked"' : '';
        return '<label class="checkbox"><input type="checkbox" name="' . $this->htmlName
        . '" id="' . $this->htmlName . '" '
        . $checked . '> ' . $this->field['label'] . '</label>';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabelText()
    {
        // Label для checkbox выводится справа от него, а не слева,
        // поэтому для левого label возвращаем пустую строку
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForList($values, $fieldName)
    {
        return ($values[$fieldName] == 1) ? 'Да' : 'Нет';
    }

    /**
     * {@inheritdoc}
     */
    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = ($request->$fieldName == '') ? 0 : 1;
        return $this->newValue;
    }
}
