<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace FormPhp\Validator\ReCaptcha;

use FormPhp\Validator\AbstractValidator;

/**
 * Валидатор, капчи от Google
 *
 */
class Controller extends AbstractValidator
{
    protected $errorMsg = "Капча не пройдена";
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
        $request = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $this->options['secretKey'] . '&response=';
        $request .= $value;
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $request .= '&remoteip=' . $_SERVER['REMOTE_ADDR'];
        }
        $response = file_get_contents($request);
        $obj = json_decode($response);
        return !($obj->success != true);
    }

    /**
     * Получение javascript для валидации на стороне клиента
     * @return string
     */
    public function getCheckJs()
    {
        $msg = $this->getErrorMsg();
        return <<<JS
            function validateRecaptcha(e, messages) {
                    return true;
                }
JS;
    }
}
