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

    /** @var View Объект вида — twig-шаблонизатор. В рамках API используется только для показа 404 ошибок */
    protected $view;

    /**
     * Действие при некорректных запросах к API системы (обработка ошибки 404)
     */
    public function error404Action()
    {
        $this->templateInit('404.twig');
        $this->view->title = 'Страница не найдена';
        $text = $this->view->render();
        return $text;
    }

    /**
     * Инициализация twig-шаблона
     *
     * @param string $tplName Название файла шаблона (с путём к нему)
     */
    public function templateInit($tplName = '')
    {
        // Проверяем, присутствует ли указанный файл шаблона на диске
        if (!stream_resolve_include_path($tplName)) {
            echo 'Нет файла шаблона ' . $tplName;
            exit;
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        $config = Config::getInstance();
        $folders = array($tplRoot);
        $this->view = new View($folders, $config->cache['templateSite']);
        $this->view->loadTemplate($tplName);
    }


    /**
     * Реакиця контроллера на запрос
     *
     * @param Router $router
     * @return string Содержимое отображаемой страницы
     */
    public function run(Router $router)
    {
        if ($router->is404()) {
            $actionName = 'error404';
        } else {
            // Определяем и вызываем требуемый action у контроллера
            $request = new Request();
            $actionName = $request->action;
        }

        $actionName = $actionName . 'Action';

        if (method_exists($this, $actionName)) {
            // Вызываемый action существует, запускаем его
            $content = $this->$actionName();
        } else {
            // Вызываемый action отсутствует, запускаем 404 ошибку
            $content = $this->error404Action();
            $router->is404 = true;
        }
        return $content;
    }
}
