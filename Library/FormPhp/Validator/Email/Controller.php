<?php

namespace FormPhp\Validator\Email;


use FormPhp\Validator\AbstractValidator;

/**
 * Валидатор, проверяющий наличие значения в элементе формы
 *
 */
class Controller extends AbstractValidator
{
    protected $errorMsg = "Неверно заполнен адрес электронной почты!";
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
        $pattern = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/im';
        return (preg_match($pattern, $value) === 1);
    }

    /**
     * Получение javascript для валидации на стороне клиента
     * @return string
     */
    public function getCheckJs()
    {
        $msg = $this->getErrorMsg();
        return <<<JS
            function validateEmail(e, messages) {
                var pattern = new RegExp(/^[\w-\.]+@[\w-]+\.[a-z]{2,4}$/i);
                var r = pattern.test(e);
                if (!r && e != '') {
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
