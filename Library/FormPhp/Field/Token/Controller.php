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
        // Так как функция появилась только в PHP 5.4, то требуется проверка
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $xhtml = ($this->xhtml) ? '/' : '';
        $val = crypt(session_id(), session_id());
        return '<input type="hidden" name="_token" value="' . $val . '" ' . $xhtml . '>' . "\n";
    }
}
