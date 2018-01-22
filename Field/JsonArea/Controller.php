<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\JsonArea;

use Ideal\Field\AbstractController;

/**
 * Отображение редактирования поля в админке в виде textarea.
 * В базе данные хранятся в виде json-представления
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'phones' => array(
 *         'label' => 'Телефоны',
 *         'sql'   => 'text',
 *         'type'  => 'Ideal_JsonArea'
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
        $value = $this->getValue();
        return
            '<textarea class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '">' . $value . '</textarea>';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $value = json_decode(parent::getValue(), true);
        if (!empty($value) && is_array($value)) {
            $value = implode("\n", $value);
        }
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function pickupNewValue()
    {
        $value = parent::pickupNewValue();
        if (!empty($value)) {
            $value = preg_split('/\s/', $value);
            $value = array_filter($value);
            $value = json_encode(array_values($value));
        }
        return $value;
    }
}
