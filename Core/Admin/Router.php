<?php
namespace Ideal\Core\Admin;

use Ideal\Core\Config;
use Ideal\Core\Util;
use Ideal\Core\PluginBroker;
use Ideal\Core\Request;

class Router
{
    protected $path = array();
    protected $controllerName = '';
    public $is404 = false;
    protected $par;


    public function __construct()
    {
        $pluginBroker = PluginBroker::getInstance();
        $pluginBroker->makeEvent('onPreDispatch', $this);

        if (count($this->path) == 0) {
            $this->path = $this->routeByPar();
        }

        $pluginBroker->makeEvent('onPostDispatch', $this);
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

        // TODO тут вообще то надо брать не последний элемент, а предпоследний
        $end = end($this->path);

        $controllerName = Util::getClassName($end['structure'], 'Structure') . '\\Admin\\Controller';

        return $controllerName;
    }


    protected function routeByPar()
    {
        // Определяем стартовую структуру
        $nextStructure = $this->getStartStructure();

        $par = explode('-', $this->par);

        unset($par[0]); // убираем первый элемент - ID начальной структуры
        $path = array($nextStructure);
        $structurePath = $nextStructure['ID'];

        // Определяем оставшиеся элементы пути
        do {
            $modelClassName = Util::getClassName($nextStructure['structure'], 'Structure') . '\\Admin\\Model';
            $structure = new $modelClassName($structurePath);
            $par = $structure->detectPageByIds($par);
            if ($par == 404) {
                $this->is404 = true;
                return $structure;
            }
            $newPath = $structure->getPath();
            $path = array_merge($path, $newPath);
            $nextStructure = end($path);
            $structurePath .= '-' . $nextStructure['ID'];
        } while (count($par) != 0);

        return $path;
    }


    protected function getStartStructure()
    {
        // Инициализируем объект запроса
        $request = new Request();
        $par = $request->par;

        $config = Config::getInstance();
        $structures = $config->structures;

        // Определяем стартовую структуру
        $nextStructure = '';
        if ($par == '') {
            $nextStructure = reset($structures);
            $par = array($nextStructure['ID']);
            $this->par = $nextStructure['ID'];
            $request->par = $this->par;
        } else {
            $this->par = $par;
            $par = explode('-', $par);
            foreach ($structures as $v) {
                if ($v['ID'] == $par[0]) {
                    $nextStructure = $v;
                    break;
                }
            }
        }

        if ($nextStructure == '') {
            echo 'Неправильный ID первой структуры: ' . $par;
            exit;
        }

        return $nextStructure;
    }
}