<?php

namespace FormPhp\Field\Referer;


use FormPhp\Field\AbstractField;

/**
 * Простое текстовое поле ввода
 *
 */
class Controller extends AbstractField
{
    protected function getValue()
    {
        return (isset($_COOKIE['referrer'])) ? $_COOKIE['referrer'] : 'empty';
    }
}
