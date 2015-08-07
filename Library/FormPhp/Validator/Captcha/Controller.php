<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace FormPhp\Validator\Captcha;

use FormPhp\Validator\AbstractValidator;

/**
 * Валидатор, проверяющий соответствие captcha
 *
 */
class Controller extends AbstractValidator
{
    protected $errorMsg = "Неверно введены цифры, изображенные на рисунке!";
    /**
     * Проверка введённого пользователем значения
     *
     * @param string $value Введённое пользователем значение
     * @return bool
     */
    public function checkValue($value)
    {
        if (empty($value)) {
            return false;
        }
        $c = md5($value);
        if (session_id() == '') {
            session_start();
        }
        if ($c == $_SESSION['cryptcode']) {
            $_SESSION['cryptcptuse'] = 0;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Получение javascript для валидации на стороне клиента
     * @return string
     */
    public function getCheckJs()
    {
        $msg = $this->getErrorMsg();
        return <<<JS
            function validateCaptcha(e, messages) {
                $.ajax({
                        type: 'GET',
                        url: '/?mode=ajax&controller=FormPhp\\\\Validator\\\\Captcha&action=checkCaptcha&value=' + e,
                        async: false,
                        dataType: 'json',
                        success: function (data) {
                            if (data.response == false) {
                                messages.errors[messages.errors.length] = "{$msg}";
                                messages.validate = false;
                            } else {
                                messages.validate = true;
                            }
                        }
                    });
                    return messages;
                }
JS;
    }
}
