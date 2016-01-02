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

    /** @var Model Модель для обработки 404-ых ошибок */
    protected $error404 = null;

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
        if ($request->mode == 'ajax') {
            $this->controllerName = $request->controller . '\\AjaxController';
            return;
        }

        $pluginBroker = PluginBroker::getInstance();
        $pluginBroker->makeEvent('onPreDispatch', $this);

        $this->error404 = new Error404\Model();

        if (is_null($this->model)) {
            $this->model = $this->routeByPar();
        }

        $pluginBroker->makeEvent('onPostDispatch', $this);

        // Инициализируем данные модели
        $this->model->initPageData();

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
        if ($request->mode == 'ajax-model' && $request->controller != '') {
            // Если это ajax-вызов с явно указанным namespace класса ajax-контроллера
            return $request->controller . '\\AjaxController';
        }

        $path = $this->model->getPath();
        $end = end($path);

        if ($request->mode == 'ajax-model' && $request->controller == '') {
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
}
