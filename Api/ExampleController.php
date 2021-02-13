<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * Вызов метода API осуществляется путём http-запроса:
 * http://example.com/api/Example?action=info&token=123
 * где
 * Example — имя вызываемого контроллера
 * action — имя вызываемого экшена контроллера
 * token — авторизационный токен для получения доступа к сайту
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Api;

use Ideal\Core\Api\Controller;
use Ideal\Core\Api\Router;
use Ideal\Core\Config;
use Ideal\Core\Request;

class ExampleController extends Controller
{
    /**
     * Обязательный метод проверки авторизации API-запроса
     * Неавторизированные запросы всегда отдают 404-ую страницу
     *
     * @param Router $router
     * @return bool
     */
    public function authorize(Router $router)
    {
        $request = new Request();
        $token = $request->token;

        // Проверяем правильность переданного токена для авторизации
        //$config = Config::getInstance();
        //return $token === $config->yandex['token'];
        return false; // заглушка, всегда возвращающая статус неавторизованности
    }

    /**
     * Реакиця контроллера на запрос
     *
     * @param Router $router
     * @return string Содержимое отображаемой страницы
     */
    public function infoAction(Router $router)
    {
        $response = json_encode(array('success' => true), JSON_FORCE_OBJECT);
        $this->jsonResponse = true;

        return $response;
    }
}
