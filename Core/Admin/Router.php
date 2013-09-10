<?php
namespace Ideal\Core\Admin;

use Ideal\Core\Config;
use Ideal\Core\Util;
use Ideal\Core\PluginBroker;
use Ideal\Core\Request;

class Router
{
    /** @var Model Модель активной страницы */
    protected $model = null;
    /** @var string Название контроллера активной страницы */
    protected $controllerName = '';
    /** @var bool Флаг 404-ошибки */
    public $is404 = false;

    /**
     * Производит роутинг исходя из запрошенного URL-адреса
     *
     * Конструктор генерирует событие onPreDispatch, затем определяет модель активной страницы
     * и генерирует событие onPostDispatch.
     * В результате работы конструктора инициализируются переменные $this->model и $this->ControllerName
     */
    public function __construct()
    {
        $pluginBroker = PluginBroker::getInstance();
        $pluginBroker->makeEvent('onPreDispatch', $this);

        if (is_null($this->model)) {
            $this->model = $this->routeByPar();
        }

        $pluginBroker->makeEvent('onPostDispatch', $this);
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

        $path = $this->model->getPath();
        $end = end($path);

        $controllerName = Util::getClassName($end['structure'], 'Structure') . '\\Admin\\Controller';

        return $controllerName;
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

        $modelClassName = Util::getClassName($path[0]['structure'], 'Structure') . '\\Admin\\Model';
        /* @var $structure Model */
        $structure = new $modelClassName('0-' . $prevStructureId);

        // Запускаем определение пути и активной модели по $par
        $model = $structure->detectPageByIds($path, $par);

        if (!is_object($model) && ($model == 404)) {
            // Если модель сообщила, что такой путь не найден — ставим флаг is404 и выходим
            $this->is404 = true;
            return $structure;
        }

        return $model;
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

}
