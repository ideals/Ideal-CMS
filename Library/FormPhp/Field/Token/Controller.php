<?php

namespace FormPhp\Field\Token;


use FormPhp\Field\AbstractField;

/**
 * Cкрытое поля с токеном для CSRF-защиты
 *
 */
class Controller extends AbstractField
{
    public function getInputText()
    {
        $xhtml = ($this->xhtml) ? '/' : '';
        return '<input id="token" type="hidden" name="_token" value="" ' . $xhtml . '>' . "\n";
    }

    public function getValueForNoSpam()
    {
        return crypt(session_id());
    }
}
