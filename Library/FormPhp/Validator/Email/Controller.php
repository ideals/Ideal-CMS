<?php

namespace FormPhp\Validator\Email;


use FormPhp\Validator\AbstractValidator;

/**
 * Валидатор, проверяющий наличие значения в элементе формы
 *
 */
class Controller extends AbstractValidator
{

    /**
     * Проверка введённого пользователем значения
     *
     * @param string $value Введённое пользователем значение
     * @return bool
     */
    public function checkValue($value)
    {
        if ($value == '') {
            return true;
        }
        $filter = filter_var($value, FILTER_VALIDATE_EMAIL);
        return ($filter == $value);
    }

    /**
     * Получение javascript для валидации на стороне клиента
     * @return string
     */
    public function getCheckJs()
    {
        return <<<JS
            function validateEmail(e, formId, input) {
                var pattern = new RegExp(/^[\w-\.]+@[\w-]+\.[a-z]{2,4}$/i);
                var r = pattern.test(e);
                if (!r) {
                    input.addClass('error-email error');
                    return false;
                } else {
                    input.removeClass('error-email');
                    input.removeClass('error');
                    return true;
                }
            }
JS;
    }
}
