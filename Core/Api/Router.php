<?php
namespace Ideal\Core\Api;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Structure\Error404;
use Ideal\Core\PluginBroker;

/**
 *  Производит роутинг запросов к API системы
 */
class Router
{

    /** @var string Название контроллера обрабатывающего запрос */
    protected $controllerName = '';

    /** @var Model Модель для обработки 404-ых ошибок */
    protected $error404 = null;

    /** @var bool Флаг 404-ошибки */
    public $is404 = false;

    /**
     * Конструктор генерирует события onPreDispatch и onPostDispatch,
     * а так же определяет модель обрабочика 404 ошибки.
     */
    public function __construct()
    {
        $pluginBroker = PluginBroker::getInstance();
        $pluginBroker->makeEvent('onPreDispatch', $this);
        $pluginBroker->makeEvent('onPostDispatch', $this);
        $this->error404 = new Error404\Model();
    }

    /**
     * Возвращает путь до контроллера ответственного за обработку запроса
     *
     * @return string Название контроллера
     */
    public function getControllerName()
    {

        if ($this->controllerName != '') {
            return $this->controllerName;
        }

        $request = new Request();
        $path = explode('/', ltrim($_SERVER['REQUEST_URI'], '/'));

        // Определяем название контроллера и экшена
        if (count($path) == 3) {
            $this->detectController($path[1]);
            if (!$this->is404) {
                list($request->action, ) = explode('?', $path[2]);
                list($namespace) = explode('\\', ltrim($this->controllerName, '\\'));
                if ($namespace != 'Ideal' || !$request->action) {
                    // Не правильный формат обращений к API
                    $this->is404 = true;
                }
            }
        } elseif (count($path) == 4) {
            $this->detectController($path[2]);
            if (!$this->is404) {
                list($request->action, ) = explode('?', $path[3]);
                list($namespace) = explode('\\', ltrim($this->controllerName, '\\'));
                if ($namespace == 'Ideal' || !$request->action) {
                    // Не правильный формат обращений к API
                    $this->is404 = true;
                }
            }
        } else {
            $this->is404 = true;
        }

        return $this->controllerName ? $this->controllerName : '\\Ideal\\Core\\Api\\Controller';
    }

    /**
     * Устанавливает название контроллера
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
     * Возвращает статус 404-ошибки,
     */
    public function is404()
    {
        return $this->is404;
    }

    /**
     * Возвращает значение флага отпрваки сообщения о 404ой ошибке
     */
    public function send404()
    {
        return $this->error404->send404();
    }

    /**
     * Ищет контроллер ответственный за обработку запроса
     * @param $controllerName
     */
    private function detectController($controllerName)
    {
        $config = Config::getInstance();
        $controllerPath = stream_resolve_include_path($controllerName . 'Controller.php');
        $controllerPath = str_replace(stream_resolve_include_path($config->cmsFolder), '', $controllerPath);
        $controllerPath = str_replace('/', '\\', $controllerPath);
        $controllerPath = str_replace('.php', '', $controllerPath);

        // Если контроллер не найден устанавливаем признак 404 ошибки
        if (!$controllerPath) {
            $this->is404 = true;
        } else {
            $this->controllerName = $controllerPath;
        }
    }
}
