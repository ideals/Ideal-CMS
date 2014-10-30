<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

use Ideal\Core\Admin\Router;

/**
 * Class AjaxController
 */
class AjaxController
{
    /** @var Model Модель соответствующая этому контроллеру */
    protected $model;

    /**
     * Генерация контента страницы для отображения в браузере
     *
     * @param Router $router
     * @return string Содержимое отображаемой страницы
     */
    public function run(Router $router)
    {
        $this->model = $router->getModel();

        // Определяем и вызываем требуемый action у контроллера
        $request = new Request();
        $actionName = $request->action;
        if ($actionName == '') {
            $actionName = 'index';
        }
        $actionName = $actionName . 'Action';

        $this->$actionName();
    }

    /**
     * Получение дополнительных HTTP-заголовков
     * По умолчанию система ставит только заголовок Content-Type, но и его можно
     * переопределить в этом методе.
     *
     * @return array Массив где ключи - названия заголовков, а значения - содержание заголовков
     */
    public function getHttpHeaders()
    {
        return array(
            'X-Robots-Tag' => 'noindex, nofollow'
        );
    }


}
