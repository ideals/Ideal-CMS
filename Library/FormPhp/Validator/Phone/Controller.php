<?php

namespace FormPhp\Validator\Phone;


use FormPhp\Validator\AbstractValidator;

/**
 * Валидатор, проверяющий наличие значения в элементе формы
 *
 */
class Controller extends AbstractValidator
{
    protected $errorMsg = "Неверно заполнен номер телефона!";
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
        preg_match_all('/[0-9]/i', $value, $result);
        if (isset($result[0]) && (count($result[0]) < 7)) {
            return false;
        }
        return true;
    }

    /**
     * Получение javascript для валидации на стороне клиента
     * @return string
     */
    public function getCheckJs()
    {
        $msg = $this->getErrorMsg();
        return <<<JS
            function validatePhone(e, messages) {
                var r = e.match(/[0-9]/g);
                if (r != null) {
                    r = r.length;
                } else {
                    r = 0;
                }
                if (r < 7 && e != '') {
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
