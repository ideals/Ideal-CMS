<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

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
     * @param \Ideal\Core\Site\Router | \Ideal\Core\Admin\Router $router
     * @return string Содержимое отображаемой страницы
     */
    public function run($router)
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

    /**
     * Генерация шаблона отображения
     *
     * @param string $tplName
     */
    public function templateInit($tplName = '')
    {
        if (!stream_resolve_include_path($tplName)) {
            echo 'Нет файла шаблона ' . $tplName;
            exit;
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        // Определяем корневую папку системы для подключение шаблонов из любой вложенной папки через их путь
        $config = Config::getInstance();
        $cmsFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder;

        $folders = array_merge(array($tplRoot, $cmsFolder));
        $this->view = new \Ideal\Core\View($folders, $config->cache['templateSite']);
        $this->view->loadTemplate($tplName);
    }
}
