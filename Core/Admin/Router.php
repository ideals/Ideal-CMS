<?php
namespace Ideal\Core\Admin;

use Ideal\Core\Config;
use Ideal\Core\PluginBroker;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Structure\Error404;

class Router
{

    /** @var string Название контроллера активной страницы */
    protected $controllerName = '';

    /** @var Model Модель активной страницы */
    protected $model = null;

    /**
     * Производит роутинг исходя из запрошенного URL-адреса
     *
     * Конструктор генерирует событие onPreDispatch, затем определяет модель активной страницы
     * и генерирует событие onPostDispatch.
     * В результате работы конструктора инициализируются переменные $this->model и $this->ControllerName
     */
    public function __construct()
    {
        $request = new Request();

        $pluginBroker = PluginBroker::getInstance();
        $pluginBroker->makeEvent('onPreDispatch', $this);

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
            $this->controllerName = '\\Ideal\\Structure\\User\\Admin\\Controller';
            $request->action = 'accessDenied';
        }

        // Определяем корректную модель на основании поля structure
        $this->model = $this->model->detectActualModel();
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
            $path = array($config->getStartStructure());
            $prevStructureId = $path[0]['ID'];
            $par = array();
        } else {
            // par задан, нужно его разложить в массив
            $par = explode('-', $par);
            // Определяем первую структуру
            $prevStructureId = $par[0];
            $path = array($config->getStructureById($prevStructureId));
            unset($par[0]); // убираем первый элемент - ID начальной структуры
        }

        if (!isset($path[0]['structure'])) {
            // По par ничего не нашлось, берём стартовую структуру из списка структуру
            $path = array($config->getStartStructure());
            $prevStructureId = $path[0]['ID'];
            $par = array();
        }

        $modelClassName = Util::getClassName($path[0]['structure'], 'Structure') . '\\Admin\\Model';
        /* @var $structure Model */
        $structure = new $modelClassName('0-' . $prevStructureId);

        // Запускаем определение пути и активной модели по $par
        $model = $structure->detectPageByIds($path, $par);

        return $model;
    }

    /**
     * Возвращает название контроллера для активной страницы
     *
     * @return string Название контроллера
     */
    public function getControllerName()
    {
        if ($this->controllerName != '') {
            return $this->controllerName;
        }

        $request = new Request();
        // Проверка на простой AJAX-запрос
        if ($request->mode == 'ajax' && $request->controller != '') {
            $controllerName = $request->controller . '\\AjaxController';
            if (class_exists($controllerName)) {
                // Если контроллер в запросе указан и запрошенный класс существует
                // то устанавливаем контроллер и завершаем роутинг
                if (!empty($request->action) && method_exists($controllerName, $request->action . 'Action')) {
                    $this->controllerName = $controllerName;
                }
            }
        }
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

        $controllerName = Util::getClassName($end['structure'], 'Structure') . '\\Admin\\Controller';

        return $controllerName;
    }

    /**
     * Устанавливает название контроллера для активной страницы
     *
     * Обычно используется в обработчиках событий onPreDispatch, onPostDispatch
     *
     * @param $name string Название контроллера
     */
    public function setControllerName($name)
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
     * @param $model Model Устанавливает модель, найденную роутером (обычно использется в плагинах)
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Возвращает статус 404-ошибки, есть он или нет
     */
    public function is404()
    {
        return $this->model->is404;
    }
}
