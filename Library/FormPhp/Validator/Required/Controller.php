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
        function validatorRequired(e, formName, inputName) {
            return $(e).val() == ''
        }
JS;
    }
}
