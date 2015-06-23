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
        return '<input type="hidden" name="_token" value="' . crypt(session_id()) . '" ' . $xhtml . '>' . "\n";
    }
}
