<?php
namespace Ideal\Structure\Service\Admin;

use Ideal\Core\Config;
use Ideal\Core\Db;

class ModelAbstract extends \Ideal\Core\Admin\Model
{
    protected $menu = array();

    public function detectPageByIds($par)
    {
        $menu = $this->getMenu();
        $first = reset($par);

        if ($first) {
            foreach ($menu as $item) {
                if ($item['ID'] == $first) {
                    break;
                }
            }
        } else {
            $item = reset($menu);
        }


        $this->path[] = $item;

        return array();
    }


    public function getMenu()
    {
        if (count($this->menu) > 0) {
            return $this->menu;
        }

        // Считываем конфиги из папки Ideal/Service и Custom/Service
        $actions = array_merge(
            $this->getActions('Ideal/Structure/Service'),
            $this->getActions('Custom/Structure/Service'),
            $this->getModulesActions('Modules'),
            $this->getModulesActions('Custom/Modules')
        );

        // Сортируем экшены по полю pos
        usort($actions, function($a, $b){
            return ($a['pos'] - $b['pos']);
        });

        $this->menu = $actions;
        return $actions;
    }


    protected function getActions($folder)
    {
        $config = Config::getInstance();
        $actions = array();
        $dir = stream_resolve_include_path($config->cmsFolder . '/' . $folder);
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' OR $file == '..' OR $file == 'Admin') continue;
                if (!is_dir($dir . '/' . $file)) continue; // пропускаем файлы, работаем только с папками

                $action = include($dir . '/' . $file . '/config.php');
                $actions[$action['ID']] = $action;
            }
        }
        return $actions;
    }


    protected function getModulesActions($folder)
    {
        $config = Config::getInstance();
        $actions = array();
        $dir = stream_resolve_include_path($config->cmsFolder . '/' . $folder);
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' OR $file == '..' OR $file == '.hg') continue;
                if (!is_dir($dir . '/' . $file)) continue; // пропускаем файлы, работаем только с папками

                $actions = array_merge($actions,
                    $this->getActions($folder . '/' . $file . '/Structure/Service'));
            }
        }
        return $actions;
    }


    public function setObjectNew()
    {

    }
}