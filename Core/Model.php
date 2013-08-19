<?php
namespace Ideal\Core;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field\Url;
use Ideal\Core\Pagination;


abstract class Model
{
    public $params;
    public $fields;
    public $object;
    protected $_table;
    protected $structurePath;
    protected $path = array();
    protected $parentUrl;
    protected $module;

    public function __construct($structurePath)
    {
        $this->structurePath = $structurePath;

        $config = Config::getInstance();

        $parts = preg_split('/[_\\\\]+/', get_class($this));
        $this->module = $parts[0];
        $module = ($this->module == 'Ideal') ? '' : $this->module . '/';
        $type = $parts[1]; // Structure или Template
        $structureName = $parts[2];
        $structureFullName = $this->module . '_' . $structureName;

        if ($structureName == 'Home') {
            $type = 'Home';
        }

        switch($type):
            case 'Home':
                // Находим начальную структуру
                $structures = $config->structures;
                $structure = reset($structures);
                $type = $parts[1];
                $structureName = $structure['structure'];
                $structureName = substr($structureName, strpos($structureName, '_') + 1);
                break;
            case 'Structure':
                $structure = $config->getStructureByName($structureFullName);
                break;
            case 'Template':
                $includeFile = $module . 'Template/' . $structureName . '/config.php';
                $structure = include($includeFile);
                if (!is_array($structure)) {
                    throw new \Exception('Не удалось подключить файл: ' . $includeFile);
                }
                break;
        endswitch;

        $this->params = $structure['params'];
        $this->fields = $structure['fields'];

        $this->_table = strtolower($config->db['prefix'] . $this->module . '_' . $type . '_' . $structureName);
    }


    public function setStructurePath($structurePath)
    {
        $this->structurePath = $structurePath;
    }


    public function getPath()
    {
        return $this->path;
    }


    public function setObjectByStructurePath($structurePath)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE structure_path='{$structurePath}'";
        $object = $db->queryArray($_sql);
        if (isset($object[0]['ID'])) {
            // TODO сделать обработку ошибки, когда по ID ничего не нашлось
            $this->object = $object[0];
        }
    }


    static function getStructureName()
    {
        $parts = explode('_', get_called_class());

        return $parts[2];
    }


    public function setPath($path)
    {
        $this->path = $path;
    }


    public function getParentUrl()
    {
        if ($this->parentUrl != '') return $this->parentUrl;

        $url = new Url\Model();
        $this->parentUrl = $url->setParentUrl($this->path);

        return $this->parentUrl;
    }


    public function getStructurePath()
    {
        return $this->structurePath;
    }


    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page)
    {
        $db = Db::getInstance();
        $where = $this->getWhere("e.structure_path='{$this->structurePath}'");

        // todo сделать определение elements_site | elements_admin на основании имени класса
        $onPage = $this->params['elements_site'];

        if ($page == 0) $page = 1;
        $start = ($page - 1) * $onPage;

        $_sql = "SELECT * FROM {$this->_table} AS e {$where}
                          ORDER BY e.{$this->params['field_sort']} LIMIT {$start}, {$onPage}";
        $list = $db->queryArray($_sql);

        return $list;
    }


    /**
     * Получить общее количество элементов в списке
     * @return array Полученный список элементов
     */
    public function getListCount()
    {
        $db = Db::getInstance();
        $where = $this->getWhere("e.structure_path='{$this->structurePath}'");

        // Считываем все элементы первого уровня
        $_sql = "SELECT COUNT(e.ID) FROM {$this->_table} AS e {$where}";
        $list = $db->queryArray($_sql);

        return $list[0]['COUNT(e.ID)'];
    }


    /**
     * Добавление к where-запросу фильтра по category_id
     * @param $where Исходная WHERE-часть
     * @return string Модифицированная WHERE-часть, с расширенным запросом, если установлена GET-переменная category
     */
    protected function getWhere($where)
    {
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }
        return $where;
    }


    /**
     * Получение листалки для шаблона и стрелок вправо/влево
     * @param $pageName Название get-параметра, содержащего страницу
     * @return mixed
     */
    public function getPager($pageName)
    {
        $request = new Request();
        $query = $request->getQueryWithout($pageName);
        $page = intval($request->{$pageName});
        $page = ($page == 0) ? 1 : $page;

        // todo сделать определение elements_site | elements_admin на основании имени класса
        $onPage = $this->params['elements_site'];

        $countList = $this->getListCount();

        $pagination = new Pagination();
        $pager['pages'] = $pagination->getPages($countList, $onPage, $page, $query, 'page');
        $pager['prev'] = $pagination->getPrev();
        $pager['next'] = $pagination->getNext();

        return $pager;
    }



}
