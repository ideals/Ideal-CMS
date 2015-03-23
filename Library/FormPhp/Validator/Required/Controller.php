<?php

namespace FormPhp\Validator\Required;


use FormPhp\Validator\AbstractValidator;

/**
 * Валидатор, проверяющий наличие значения в элементе формы
 *
 */
class Controller extends AbstractValidator
{
    protected $errorMsg = "";
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
        $msg = $this->getErrorMsg();
        return <<<JS
        function validateRequired(e, messages) {
            if ((e) == '') {
                messages.notValid = 'Заполните все поля, отмеченные звездочкой!';
                messages.errors[messages.errors.length] = "{$msg}";
                messages.validate = false;
                return messages;
            } else {
                messages.validate = true;
                return messages;
            }
        }
JS;
    }
}
