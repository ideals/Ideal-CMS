<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core\Api;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\View;

class Controller
{
    /** @var bool Признак необходимости переопределения типа отдаваемого контента */
    protected $jsonResponse = true;

    /**
     * Реакиця контроллера на запрос
     *
     * @param Router $router
     * @return string Ответ системы на запрос
     */
    public function run(Router $router)
    {
        if ($router->is404()) {
            return '';
        }

        // Определяем и вызываем требуемый action у контроллера
        $request = new Request();
        $actionName = $request->action;
        $actionName = empty($actionName) ? 'index' : $actionName;

        $actionName = $actionName . 'Action';

        if (!method_exists($this, $actionName)) {
            // Вызываемый action отсутствует, запускаем 404 ошибку
            $content = $this->error404Action();
            $router->is404 = true;
            return $content;
        }

        // Проверяем, авторизован ли доступ к API
        if (!$this->authorize($router)) {
            // Старый токен не совпадает с переданным значением, отдаём 404
            $content = $this->error404Action();
            $router->is404 = true;
            return $content;
        }

        // Вызываемый action существует и доступ авторизован, запускаем его
        $content = $this->$actionName($router);

        return $content;
    }

    /**
     * Получение дополнительных HTTP-заголовков
     *
     * @return array Массив где ключи - названия заголовков, а значения - содержание заголовков
     */
    public function getHttpHeaders()
    {
        if ($this->jsonResponse) {
            return array('content-type' => 'application/json');
        } else {
            return array();
        }
    }

    /**
     * Обязательный метод проверки авторизации API-запроса
     * Неавторизированные запросы всегда отдают 404-ую страницу
     *
     * @param Router $router
     * @return bool
     */
    public function authorize(Router $router)
    {
        return false;
    }
}
