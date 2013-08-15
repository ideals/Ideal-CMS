<?php
namespace Ideal\Core\Site;

use Ideal\Core\PluginBroker;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\Util;

class Router
{
    protected $path = array();
    protected $model;
    protected $controllerName = '';
    public $is404 = false;


    public function __construct()
    {
        // Проверка на AJAX-запрос
        $request = new Request();
        if ($request->mode == 'ajax') {
            $this->controllerName = $request->module . '\\Structure\\'
                . $request->controller . '\\Site\\AjaxController';
            return;
        }

        $pluginBroker = PluginBroker::getInstance();
        $pluginBroker->makeEvent('onPreDispatch', $this);

        if (count($this->path) == 0) {
            // С помощью плагинов страница не найдена, ищем обычным рутером
            $this->path = $this->routeByUrl();
        } else {
            // Страница была найдена с помощью плагина

            $end = end($this->path);
            $prev = prev($this->path);

            if ($prev['structure'] == $end['structure']) {
                $structurePath = $end['structure_path'];
            } else {
                $structurePath = $end['structure_path'] . $end['ID'];
            }

            $modelClassName = Util::getClassName($end['structure'], 'Structure') . '\\Site\\Model';

            /** @var $structure \Ideal\Core\Site\Model */
            $structure = new $modelClassName($structurePath);
            $structure->setPath($this->path);
            $structure->object = $end;
            $this->model = $structure;
        }

        $pluginBroker->makeEvent('onPostDispatch', $this);
    }


    public function setPath($path)
    {
        $this->path = $path;
    }


    public function getPath()
    {
        return $this->path;
    }


    public function setControllerName($name)
    {
        $this->controllerName = $name;
    }


    public function getControllerName()
    {
        if ($this->controllerName != '') {
            return $this->controllerName;
        }

        $end = end($this->path);
        if ($end['url'] == '/') {
            $end['structure'] = 'Ideal_Home';
        }

        $controllerName =  Util::getClassName($end['structure'], 'Structure') . '\\Site\\Controller';

        return $controllerName;
    }


    protected function routeByUrl()
    {
        $config = Config::getInstance();

        // Находим начальную структуру
        $structures = $config->structures;
        $startStructure = reset($structures);
        $structurePath = $startStructure['ID'];
        $this->path = array($startStructure);

        // Вырезаем стартовый URL
        $url = substr($_GET['url'], strlen($config->startUrl));
        $url = rtrim($url, '/'); // убираем завершающие слэши, если они есть (это костыль)

        // TODO здесь должно быть правильное отрезание суффикса, вместо того, что выше
        // а в htaccess убрать переадресацию по суффиксу, оставить только переадресацию
        // по отстутствующему файлу

        if ($url == '') {
            // Если главная страница
            $this->model = new \Ideal\Structure\Home\Site\Model($structurePath);
            $url = $this->model->detectPageByUrl('/', $this->path);
            if ($url != '404') {
                return $this->model->getPath();
            }
        }

        // TODO Тут можно подключить плагин для кастомного роутинга

        // Разрезаем URL на части
        $url = explode('/', $url);

        if($url[0] == 'goods'){
            $this->model = new \Shop\Structure\Good\Site\Model(6);
            $url = $this->model->detectPageByUrl($url[1], $this->path);
            if ($url != '404') {
                return $this->model->getPath();
            }
        }

        // Определяем оставшиеся элементы пути
        $nextStructure = $startStructure;
        do {
            if ($url == 404) {
                // Если на предыдущем шаге возникла ошибка 404
                $this->is404 = true;
                if (count($this->path) == 1) {
                    // Если у страницы нет ни одного существующего предка
                    $this->model = new \Ideal\Structure\Home\Site\Model($structurePath);
                    $this->model->detectPageByUrl('/', $this->path);
                    $this->path = $this->model->getPath();
                    $request = new Request();
                    $request->action = 'error404';
                    return array($startStructure, array('structure' => 'Ideal_Home', 'url' => '/', 'ID' => 0));
                }
                $url = array();
                break;
            }
            $modelClassName = Util::getClassName($nextStructure['structure'], 'Structure') . '\\Site\\Model';
            /** @var $structure \Ideal\Core\Site\Model */
            $structure = new $modelClassName($structurePath);
            // Если на предыдущем шаге не было 404 ошибки и массив $url не кончился
            $url = $structure->detectPageByUrl($url, $this->path);
            if ($url == '404') continue;
            $this->path = $structure->getPath();
            $nextStructure = end($this->path);
            $structurePath .= '-' . $nextStructure['ID'];
            $this->model = $structure;
        } while (count($url) != 0);

        return $this->path;
    }


    public function getModel()
    {
        return $this->model;
    }
}