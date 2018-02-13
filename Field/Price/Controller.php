<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Price;

use Ideal\Core\Request;

/**
 * Class Controller
 */
class Controller extends \Ideal\Field\AbstractController
{
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $value = str_replace(',', '.', htmlspecialchars($this->getValue()));
        return '<input type="number" step="0.01" class="form-control '
            . '" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '" value="' . $value . '">';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $value = intval(parent::getValue()) / 100;
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForList($values, $fieldName)
    {
        return number_format($values[$fieldName] / 100, 2, ',', ' ');
    }

    /**
     * {@inheritdoc}
     */
    public function pickupNewValue()
    {
        $request = new Request();
        $fieldName = $this->groupName . '_' . $this->name;
        $this->newValue = $request->$fieldName * 100;
        return $this->newValue;
    }
}
