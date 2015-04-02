<?php
// @codingStandardsIgnoreFile
namespace Ideal\Core;

use Ideal\Field\Url;

abstract class Model
{

    public $fields;

    /** @var bool Флаг 404-ошибки */
    public $is404 = false;

    public $params;

    protected $_table;

    protected $module;

    protected $pageData;

    protected $pageNum = 1;

    protected $pageNumTitle = ' | Страница [N]';

    protected $parentUrl;

    protected $path = array();

    protected $prevStructure;

    public function __construct($prevStructure)
    {
        $this->prevStructure = $prevStructure;

        $config = Config::getInstance();

        $parts = preg_split('/[_\\\\]+/', get_class($this));
        $this->module = $parts[0];
        $module = ($this->module == 'Ideal') ? '' : $this->module . '/';
        $type = $parts[1]; // Structure или Addon
        $structureName = $parts[2];
        $structureFullName = $this->module . '_' . $structureName;

        if ($structureName == 'Home') {
            $type = 'Home';
        }

        switch ($type) {
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
            case 'Addon':
                $includeFile = $module . 'Addon/' . $structureName . '/config.php';
                $structure = include($includeFile);
                if (!is_array($structure)) {
                    throw new \Exception('Не удалось подключить файл: ' . $includeFile);
                }
                break;
            default:
                throw new \Exception('Неизвестный тип: ' . $type);
                break;
        }

        $this->params = $structure['params'];
        $this->fields = $structure['fields'];

        $this->_table = strtolower($config->db['prefix'] . $this->module . '_' . $type . '_' . $structureName);
    }

    /**
     * Определение сокращённого имени структуры Модуль_Структура по имени этого класса
     *
     * @return string Сокращённое имя структуры, используемое в БД
     */
    public static function getStructureName()
    {
        $parts = explode('\\', get_called_class());
        return $parts[0] . '_' . $parts[2];
    }

    public function __get($name)
    {
        if ($name == 'object') {
            throw new \Exception('Свойство object упразднено.');
        }
    }

    public function detectActualModel()
    {
        $config = Config::getInstance();
        $model = $this;
        $count = count($this->path);

        $class = get_class($this);
        if ($class == 'Ideal\\Structure\\Home\\Site\\Model') {
            // В случае если у нас открыта главная страница, не нужно переопределять модель как обычной страницы
            return $model;
        }

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
                // Передача всех данных из одной модели в другую
                $model = $model->setVars($this);
            }
        }
        return $model;
    }

    /**
     * Установка свойств объекта по данным из массива $vars
     *
     * Вызывается при копировании данных из одной модели в другую
     *
     * @param array $model Массив переменных объекта
     * @return $this Либо ссылка на самого себя, либо новый объект модели
     */
    public function setVars($model)
    {
        $vars = get_object_vars($model);
        foreach ($vars as $k => $v) {
            if (in_array($k, array('_table', 'module', 'params', 'fields', 'prevStructure'))) {
                continue;
            }
            $this->$k = $v;
        }
        return $this;
    }

    // Получаем информацию о странице

    /**
     * Определение пути с помощью prev_structure по инициализированному $pageData
     *
     * @return array Массив, содержащий элементы пути к $pageData
     */
    public function detectPath()
    {
        $config = Config::getInstance();

        // Определяем локальный путь в этой структуре
        $localPath = $this->getLocalPath();

        // По первому элементу в локальном пути, опеределяем, какую структуру нужно вызвать
        if (isset($localPath[0])) {
            $first = $localPath[0];
        } else {
            $first['prev_structure'] = $this->prevStructure;
        }

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
        $path = array_merge($path, $localPath);

        return $path;
    }

    // Устанавливаем информацию о странице

    /**
     * Построение пути в рамках одной структуры.
     * Этот метод обязательно должен быть переопределён перед использованием.
     * Если он не будет переопределён, то будет вызвано исключение.
     *
     * @throws \Exception
     */
    protected function getLocalPath()
    {
        throw new \Exception('Вызов не переопределённого метода getLocalPath');
    }

    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page = null)
    {
        $where = ($this->prevStructure !== '') ? "e.prev_structure='{$this->prevStructure}'" : '';
        $where = $this->getWhere($where);

        $db = Db::getInstance();

        $_sql = "SELECT e.* FROM {$this->_table} AS e {$where} ORDER BY e.{$this->params['field_sort']}";

        if (is_null($page)) {
            $this->setPageNum($page);
        } else {
            // Определяем кол-во отображаемых элементов на основании названия класса
            $class = strtolower(get_class($this));
            $class = explode('\\', trim($class, '\\'));
            $nameParam = ($class[3] == 'admin') ? 'elements_cms' : 'elements_site';
            $onPage = $this->params[$nameParam];

            $page = $this->setPageNum($page);
            $start = ($page - 1) * $onPage;

            $_sql .= " LIMIT {$start}, {$onPage}";
        }

        $list = $db->select($_sql);

        return $list;
    }

    /**
     * Добавление к where-запросу фильтра по category_id
     *
     * @param string $where Исходная WHERE-часть
     * @return string Модифицированная WHERE-часть, с расширенным запросом, если установлена GET-переменная category
     */
    protected function getWhere($where)
    {
        if ($where != '') {
            // Убираем из строки начальные команды AND или OR
            $where = trim($where);
            $where = preg_replace('/(^AND)|(^OR)/i', '', $where);
            $where = 'WHERE ' . $where;
        }
        return $where;
    }

    public function getPageData()
    {
        if (is_null($this->pageData)) {
            $this->initPageData();
        }
        return $this->pageData;
    }

    public function setPageData($pageData)
    {
        $this->pageData = $pageData;
    }

    public function initPageData($pageData = null)
    {
        if (is_null($pageData)) {
            $this->pageData = end($this->path);
        } else {
            $this->pageData = $pageData;
        }

        // Получаем переменные шаблона
        $config = Config::getInstance();
        foreach ($this->fields as $k => $v) {
            // Пропускаем все поля, которые не являются аддоном
            if (strpos($v['type'], '_Addon') === false) {
                continue;
            }

            // В случае, если 404 ошибка, и нужной страницы в БД не найти
            if (!isset($this->pageData[$k])) {
                continue;
            }

            // Определяем структуру на основании названия класса
            $structure = $config->getStructureByClass(get_class($this));

            if ($structure === false) {
                // Не удалось определить структуру из конфига (Home)
                // Определяем структуру, к которой принадлежит последний элемент пути
                $prev = count($this->path) - 2;
                if ($prev >= 0) {
                    $prev = $this->path[$prev];
                    $structure = $config->getStructureByName($prev['structure']);
                } else {
                    throw new \Exception('Не могу определить структуру для шаблона');
                }
            }

            // Обходим все аддоны, подключенные к странице
            $addonsInfo = json_decode($this->pageData[$k]);

            foreach ($addonsInfo as $addonInfo) {
                // Инициализируем модель аддона
                $className = Util::getClassName($addonInfo[1], 'Addon') . '\\Model';
                $prevStructure = $structure['ID'] . '-' . $this->pageData['ID'];
                $addon = new $className($prevStructure);
                $addon->setParentModel($this);
                list(, $fildsGroup) = explode('_', $addonInfo[1]);
                $addon->setFieldsGroup(strtolower($fildsGroup) . '-' . $addonInfo[0]);
                $addon->pageData = $addon->getPageData();
                if (!empty($addon->pageData)) {
                    $this->pageData['addons'][] = $addon->pageData;
                }
            }
        }
    }

    /**
     * Получение листалки для шаблона и стрелок вправо/влево
     *
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

        // Определяем кол-во отображаемых элементов на основании названия класса
        $class = strtolower(get_class($this));
        $class = explode('\\', trim($class, '\\'));
        $nameParam = ($class[3] == 'admin') ? 'elements_cms' : 'elements_site';
        $onPage = $this->params[$nameParam];

        $countList = $this->getListCount();

        if (($countList > 0) && (ceil($countList / $onPage) < $page)) {
            // Если для запрошенного номера страницы нет элементов - выдать 404
            $this->is404 = true;
            return false;
        }

        $pagination = new Pagination();
        // Номера и ссылки на доступные страницы
        $pager['pages'] = $pagination->getPages($countList, $onPage, $page, $query, 'page');
        $pager['prev'] = $pagination->getPrev(); // ссылка на предыдущю страницу
        $pager['next'] = $pagination->getNext(); // cсылка на следующую страницу
        $pager['total'] = $countList; // общее количество элементов в списке

        return $pager;
    }

    /**
     * Получить общее количество элементов в списке
     *
     * @return array Полученный список элементов
     */
    public function getListCount()
    {
        $db = Db::getInstance();

        $where = ($this->prevStructure !== '') ? "e.prev_structure='{$this->prevStructure}'" : '';
        $where = $this->getWhere($where);

        // Считываем все элементы первого уровня
        $_sql = "SELECT COUNT(e.ID) FROM {$this->_table} AS e {$where}";
        $list = $db->select($_sql);

        return $list[0]['COUNT(e.ID)'];
    }

    /**
     * Получение номера отображаемой страницы
     *
     * @return int Номер отображаемой страницы
     */
    public function getPageNum()
    {
        return $this->pageNum;
    }
    
    public function getParentUrl()
    {
        if ($this->parentUrl != '') {
            return $this->parentUrl;
        }

        $url = new Url\Model();
        $this->parentUrl = $url->setParentUrl($this->path);

        return $this->parentUrl;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Получение названия основной таблицы модели
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->_table;
    }

    public function setPath($path)
    {
        $this->path = $path;
        $count = count($path);
        if ($count > 1) {
            // В случае, если не 404ая страница, то устанавливаем $this->prevStructure
            $config = Config::getInstance();
            $prev = $path[$count - 2];
            $end = end($path);
            $structure = $config->getStructureByName($prev['structure']);
            $this->prevStructure = $structure['ID'] . '-' . $end['ID'];
        }
    }

    public function getPrevStructure()
    {
        return $this->prevStructure;
    }

    public function setPrevStructure($prevStructure)
    {
        $this->prevStructure = $prevStructure;
    }

    public function setPageDataById($id)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE ID=:id";
        $pageData = $db->select($_sql, array('id' => $id));
        if (isset($pageData[0]['ID'])) {
            // TODO сделать обработку ошибки, когда по ID ничего не нашлось
            $this->setPageData($pageData[0]);
        }
    }

    /**
     * Установка номера отображаемой страницы
     *
     * С номером страницы всё понятно, а вот $pageNumTitle позволяет изменить стандартный шаблон
     * суффикса для листалки " | Страница [N]" на любой другой суффикс, где
     * вместе [N] будет подставляться номер страницы.
     *
     * @param int $pageNum Номер отображаемой страницы
     * @param null $pageNumTitle Строка для замены стандартного суффикса листалки в тайтле
     * @return int Безопасный номер страницы
     */
    public function setPageNum($pageNum, $pageNumTitle = null)
    {
        $this->pageNum = 0;
        if ($pageNum !== null) {
            $pageNum = intval(substr($pageNum, 0, 10)); // отсекаем всякую ерунду и слишком большие числа в листалке
            $this->pageNum = ($pageNum == 0) ? 1 : $pageNum;
        }

        if (!is_null($pageNumTitle)) {
            $this->pageNumTitle = $pageNumTitle;
        }

        return $this->pageNum;
    }

    /**
     * Метод используется только в моделях Template для установки модели владельца этого шаблона
     *
     * @param $model
     */
    public function setParentModel($model)
    {
        $this->parentModel = $model;
    }
}
