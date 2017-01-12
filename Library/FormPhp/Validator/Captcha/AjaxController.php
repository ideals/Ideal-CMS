<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace FormPhp\Validator\Captcha;

use Ideal\Core\Request;

class AjaxController extends \Ideal\Core\AjaxController
{
    /**
     * Проверка введённого пользователем значения
     *
     * @return string Результат проверки соответствия введённого значения captcha
     */
    public function checkCaptchaAction()
    {
        $request = new Request();
        $value = addslashes($request->value);
        $controller = new Controller();
        $response = $controller->checkValue($value);
        return json_encode(array('response' => $response), JSON_FORCE_OBJECT);
    }
}
