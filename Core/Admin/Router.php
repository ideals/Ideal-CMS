<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core\Admin;

use Ideal\Structure\Error404\Model;
use Ideal\Structure\User\Admin\Controller;
use Ideal\Core\Config;
use Ideal\Core\PluginBroker;
use Ideal\Core\Request;
use Ideal\Core\Util;

class Router
{
    /** @var string Название контроллера активной страницы */
    protected string $controllerName = '';

    /** @var Model Модель активной страницы */
    protected $model;

    /** @var Model Модель для обработки 404-ых ошибок */
    protected Model $error404;

    /**
     * Производит роутинг исходя из запрошенного URL-адреса
     *
     * Конструктор генерирует событие onPreDispatch, затем определяет модель активной страницы
     * и генерирует событие onPostDispatch.
     * В результате работы конструктора инициализируются переменные $this->model и $this->ControllerName
     */
    public function __construct()
    {
        // Проверка на простой AJAX-запрос
        $request = new Request();
        if ($request->mode == 'ajax' && $request->controller != '') {
            $controllerName = $request->controller . '\\AjaxController';
            // Если контроллер в запросе указан и запрошенный класс существует
            // то устанавливаем контроллер и завершаем роутинг
            if (class_exists($controllerName) && (!empty($request->action) && method_exists($controllerName, $request->action . 'Action'))) {
                $this->controllerName = $controllerName;
            }
        }

        $pluginBroker = PluginBroker::getInstance();
        $pluginBroker->makeEvent('onPreDispatch', $this);

        $this->error404 = new Model();

        if (is_null($this->model)) {
            $this->model = $this->routeByPar();
        }

        $pluginBroker->makeEvent('onPostDispatch', $this);

        // Инициализируем данные модели
        $this->model->initPageData();

        // Проверка прав доступа
        $aclModel = new \Ideal\Structure\Acl\Admin\Model();
        if (!$aclModel->checkAccess($this->model)) {
            // Если доступ запрещён, перебрасываем на соответствующий контроллер
            $this->controllerName = Controller::class;
            $request->action = 'accessDenied';
        }

        // Определяем корректную модель на основании поля structure
        $this->model = $this->model->detectActualModel();
    }

    /**
     * Возвращает название контроллера для активной страницы
     *
     * @return string Название контроллера
     */
    public function getControllerName()
    {
        if ($this->controllerName !== '') {
            return $this->controllerName;
        }

        if (method_exists($this->model, 'getControllerName')) {
            return $this->model->getControllerName();
        }

        $request = new Request();
        if ($request->mode == 'ajax' && $request->controller != '') {
            // Если это ajax-вызов с явно указанным namespace класса ajax-контроллера
            return $request->controller . '\\AjaxController';
        }

        $path = $this->model->getPath();
        $end = end($path);

        if ($request->mode == 'ajax' && $request->controller == '') {
            // Если это ajax-вызов без указанного namespace класса ajax-контроллера,
            // то используем namespace модели
            return Util::getClassName($end['structure'], 'Structure') . '\\Admin\\AjaxController';
        }

        return Util::getClassName($end['structure'], 'Structure') . '\\Admin\\Controller';
    }

    /**
     * Устанавливает название контроллера для активной страницы
     *
     * Обычно используется в обработчиках событий onPreDispatch, onPostDispatch
     *
     * @param $name string Название контроллера
     */
    public function setControllerName(string $name): void
    {
        $this->controllerName = $name;
    }

    /**
     * Возвращает объект модели активной страницы
     *
     * @return Model Инициализированный объект модели активной страницы
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Возвращает статус 404-ошибки, есть он или нет
     */
    public function is404()
    {
        return $this->model->is404;
    }

    /**
     * Возвращает значение флага отпрваки сообщения о 404ой ошибке
     */
    public function send404()
    {
        return $this->error404->send404();
    }

    /**
     * Определение модели активной страницы и пути к ней на основе переменной $_GET['par']
     *
     * @return Model Модель активной страницы
     */
    protected function routeByPar()
    {
        $config = Config::getInstance();

        // Инициализируем $par — массив ID к активному объекту
        $request = new Request();
        $par = $request->par;

        if ($par == '') {
            // par не задан, берём стартовую структуру из списка структур
            $path = [$config->getStartStructure()];
            $prevStructureId = $path[0]['ID'];
            $par = [];
        } else {
            // par задан, нужно его разложить в массив
            $par = explode('-', $par);
            // Определяем первую структуру
            $prevStructureId = $par[0];
            $path = [$config->getStructureById($prevStructureId)];
            unset($par[0]); // убираем первый элемент - ID начальной структуры
        }

        if (!isset($path[0]['structure'])) {
            // По par ничего не нашлось, берём стартовую структуру из списка структуру
            $path = [$config->getStartStructure()];
            $prevStructureId = $path[0]['ID'];
            $par = [];
        }

        $modelClassName = Util::getClassName($path[0]['structure'], 'Structure') . '\\Admin\\Model';
        /* @var $structure Model */
        $structure = new $modelClassName('0-' . $prevStructureId);

        // Запускаем определение пути и активной модели по $par
        $model = $structure->detectPageByIds($path, $par);

        return $model;
    }
}
