<?php

namespace FormPhp\Field\OrderType;


use FormPhp\Field\AbstractField;

/**
 * Поле "Тип заказа".
 *
 */
class Controller extends AbstractField
{
    public function getInputText()
    {
        $xhtml = ($this->xhtml) ? '/' : '';
        $value = isset($this->options['value']) ? $this->options['value'] : '';
        return '<input id="order_type" type="hidden" name="order_type" value="' . $value . '" ' . $xhtml . '>' . "\n";
    }
}
