<?php

namespace FormPhp\Validator\Required;


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
        return !empty($value);
    }

    /**
     * Получение javascript для валидации на стороне клиента
     * @return string
     */
    public function getCheckJs()
    {

        return <<<JS
        function validateRequired(e, formId, input) {
            if ((e) == '') {
                input.addClass('error-required error');
                return false;
            } else {
                input.removeClass('error-required');
                input.removeClass('error');
                return true;
            }
        }
JS;
    }
}
