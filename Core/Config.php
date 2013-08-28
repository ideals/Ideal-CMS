<?php
namespace Ideal\Core;

class Config
{
    private static $instance;
    private $array = array();


    public function loadSettings()
    {
        // Подключаем описание данных для БД
        $this->import(require_once($this->cmsFolder . '/config.php'));

        // Подключаем файл с переменными изменяемыми в админке
        $this->import(require_once($this->cmsFolder . '/site_data.php'));

        $this->loadStructures();
    }


    public function loadStructures()
    {
        // Проходимся по всем конфигам подключенных структур и добавляем их в общий конфиг
        $structures = $this->structures;
        foreach($structures as $k => $structure) {
            list($module, $struct) = explode('_', $structure['structure'], 2);
            $module = ($module == 'Ideal') ? '' : $module . '/';
            $fileName = $module . 'Structure/' . $struct . '/config.php';
            $arr = require_once($fileName);
            $structures[$k] = array_merge($structure, $arr);
        }

        // Строим массив соответствия порядковых номеров структур их названиям
        $structuresNum = array();
        foreach ($structures as $num => $structure) {
            $structureName = $structure['structure'];
            if (isset($structuresNum[$structureName])) {
                Core_Util::addError('Повторяющееся наименование структуры; ' . $structureName);
            }
            $structuresNum[$structureName] = $num;
        }

        // Проводим инъекции данных в соответствии с конфигами структур
        foreach ($structures as $structure) {
            if (!isset($structure['params']['in_structures'])) {
                // Пропускаем структуры, в которых не заданы инъекции
                continue;
            }
            foreach ($structure['params']['in_structures'] as $structureName) {
                $num = $structuresNum[$structureName];
                $structures[$num]['params']['structures'][] = $structure['structure'];
            }
        }
        $this->structures = $structures;
    }


    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }


    public function __get($name)
    {
        if (isset($this->array[$name])) {
            return $this->array[$name];
        }
        return '';
    }


    public function __set($name, $value)
    {
        $this->array[$name] = $value;
    }


    public function import($arr)
    {
        $this->array = array_merge($this->array, $arr);
    }


    public function getStructureByName($name)
    {
        // TODO сделать уведомление об ошибке, если такой структуры нет
        foreach($this->structures as $structure) {
            if ($structure['structure'] == $name) {
                return $structure;
            }
        }
        return false;
    }

    public function getStructureById($structureId)
    {
        // TODO сделать уведомление об ошибке, если такой структуры нет
        foreach($this->structures as $structure) {
            if ($structure['ID'] == $structureId) {
                return $structure;
            }
        }
        return false;
    }

}