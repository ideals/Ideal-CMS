<?php
namespace Ideal\Core;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Util;
use Ideal\Field\Url;
use Ideal\Core\Pagination;


abstract class Model
{
    public $params;
    public $fields;
    protected $_table;
    protected $prevStructure;
    protected $path = array();
    protected $parentUrl;
    protected $module;
    protected $prevModel;
    protected $pageData;
    /** @var bool Флаг 404-ошибки */
    public $is404 = false;

    public function __construct($prevStructure)
    {
        $this->prevStructure = $prevStructure;

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


    public function setprevStructure($prevStructure)
    {
        $this->prevStructure = $prevStructure;
    }


    public function getPath()
    {
        return $this->path;
    }

    public function setPageDataById($id)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE ID='{$id}'";
        $pageData = $db->queryArray($_sql);
        if (isset($pageData[0]['ID'])) {
            // TODO сделать обработку ошибки, когда по ID ничего не нашлось
            $this->setPageData($pageData[0]);
        }
    }


    public function setPageDataByPrevStructure($prevStructure)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE prev_structure='{$prevStructure}'";
        $pageData = $db->queryArray($_sql);
        if (isset($pageData[0]['ID'])) {
            // TODO сделать обработку ошибки, когда по prevStructure ничего не нашлось
            $this->setPageData($pageData[0]);
        }
    }

    // Получаем информацию о странице
    public  function getPageData()
    {
        if (is_null($this->pageData)) {
            $this->initPageData();
        }
        return $this->pageData;
    }

    // Устанавливаем информацию о странице
    public  function setPageData($pageData)
    {
        $this->pageData = $pageData;
    }

    public  function initPageData()
    {
        $this->pageData = end($this->path);

        // Получаем переменные шаблона
        $config = Config::getInstance();
        foreach ($this->fields as $k => $v) {
            // Пропускаем все поля, которые не являются шаблоном
            if (strpos($v['type'], '_Template') === false) continue;

            // В случае, если 404 ошибка, и нужной страницы в БД не найти
            if (!isset($this->pageData[$k])) continue;
            $className = Util::getClassName($this->pageData[$k], 'Template') . '\\Model';
            $prev = $this->path[(count($this->path) - 2)];
            $structure = $config->getStructureByName($prev['structure']);
            $prevStructure = $structure['ID'] . '-' . $this->pageData['ID'];
            $template = new $className($prevStructure);
            $this->pageData[$k] = $template->getPageData();
        }
    }

    /**
     * Определение сокращённого имени структуры Модуль_Структура по имени этого класса
     * @return string Сокращённое имя структуры, используемое в БД
     */
    static function getStructureName()
    {
        $parts = explode('\\', get_called_class());
        return $parts[0] . '_' . $parts[2];
    }


    public function setPath($path)
    {
        $config = Config::getInstance();
        $prev = $path[count($path) - 2];
        $end = end($path);
        $structure = $config->getStructureByName($prev['structure']);
        $this->prevStructure = $structure['ID'] . '-' . $end['ID'];
        $this->path = $path;
    }


    public function getParentUrl()
    {
        if ($this->parentUrl != '') return $this->parentUrl;

        $url = new Url\Model();
        $this->parentUrl = $url->setParentUrl($this->path);

        return $this->parentUrl;
    }


    public function getprevStructure()
    {
        return $this->prevStructure;
    }


    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page)
    {
        $list = array();
        $where = $this->getWhere("e.prev_structure='{$this->prevStructure}'");
        if ($where === false) return $list;
        $db = Db::getInstance();

        // todo сделать определение elements_site | elements_admin на основании имени класса
        $onPage = $this->params['elements_site'];

        if ($page == 0) $page = 1;
        $start = ($page - 1) * $onPage;

        $_sql = "SELECT e.* FROM {$this->_table} AS e {$where}
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
        $where = $this->getWhere("e.prev_structure='{$this->prevStructure}'");

        // Считываем все элементы первого уровня
        $_sql = "SELECT COUNT(e.ID) FROM {$this->_table} AS e {$where}";
        $list = $db->queryArray($_sql);

        return $list[0]['COUNT(e.ID)'];
    }


    /**
     * Добавление к where-запросу фильтра по category_id
     * @param string $where Исходная WHERE-часть
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
     * @param string $pageName Название get-параметра, содержащего страницу
     * @return mixed
     */
    public function getPager($pageName)
    {
        // По заданному названию параметра страницы определяем номер активной страницы
        $request = new Request();
        $page = intval($request->{$pageName});
        $page = ($page == 0) ? 1 : $page;

        // Строка запроса без нашего параметра номера страницы
        $query = $request->getQueryWithout($pageName);

        // todo сделать определение elements_site | elements_admin на основании имени класса
        $onPage = $this->params['elements_site'];

        $countList = $this->getListCount();

        $pagination = new Pagination();
        // Номера и ссылки на доступные страницы
        $pager['pages'] = $pagination->getPages($countList, $onPage, $page, $query, 'page');
        $pager['prev'] = $pagination->getPrev(); // ссылка на предыдущю страницу
        $pager['next'] = $pagination->getNext(); // cсылка на следующую страницу

        return $pager;
    }

    /**
     * Определение пути с помощью prev_structure по инициализированному $pageData
     * @return array Массив, содержащий элементы пути к $pageData
     */
    public function detectPath()
    {
        $config = Config::getInstance();

        // Определяем локальный путь в этой структуре
        $localPath = $this->getLocalPath();

        // По первому элементу в локальном пути, опеределяем, какую структуру нужно вызвать
        $first = $localPath[0];

        list($prevStructureId, $prevElementId) = explode('-', $first['prev_structure']);
        $structure = $config->getStructureByPrev($first['prev_structure']);

        if ($prevStructureId == 0) {
            // Если предыдущая структура стартовая — заканчиваем
            array_unshift($localPath, $structure);
            return $localPath;
        }

        // Если предыдущая структура не стартовая —
        // инициализируем её модель и продолжаем определение пути в ней
        $className = Util::getClassName($structure['structure'], 'Structure') . '\\Site\\Model';

        $structure = new $className('');
        $structure->setPageDataById($prevElementId);

        $path = $structure->detectPath();
        $path = array_merge($path, $this->getLocalPath());

        return $path;
    }

    /**
     * Построение пути в рамках одной структуры.
     * Этот метод обязательно должен быть переопределён перед использованием.
     * Если он не будет переопределён, то будет вызвано исключение.
     * @throws \Exception
     */
    protected function getLocalPath()
    {
        throw new \Exception('Вызов не переопределённого метода getLocalPath');
    }

    public function setPrevModel($prevModel)
    {
        $this->prevModel = $prevModel;
    }

    public function getPrevModel()
    {
        return $this->prevModel;
    }

    public function detectActualModel()
    {
        $config = Config::getInstance();
        $model = $this;
        $count = count($this->path);

        if ($count > 1) {
            $end = $this->path[($count - 1)];
            $prev = $this->path[($count - 2)];

            $endClass = ltrim(Util::getClassName($end['structure'], 'Structure'), '\\');
            $thisClass = get_class($this);

            // Проверяем, соответствует ли класс объекта вложенной структуре
            if (strpos($thisClass, $endClass) === false) {
                // Если структура активного элемента не равна структуре предыдущего элемента,
                // то нужно инициализировать модель структуры активного элемента
                $name = explode('\\', get_class($this));
                $modelClassName = Util::getClassName($end['structure'], 'Structure') . '\\' . $name[3] . '\\Model';
                $prevStructure = $config->getStructureByName($prev['structure']);
                /* @var $model Model */
                $model = new $modelClassName($prevStructure['ID'] . '-' . $end['ID']);
                $model->setPath($this->path);
                $model->setPrevModel($this);
                // TODO сделать метод передачи всех данных из одной модели в другую
                $model->is404 = $this->is404;
            }
        }
        return $model;
    }

    public function __get($name)
    {
        if ($name == 'object') {
            throw new \Exception('Свойство object упразднено.');
        }
    }
}
