<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Tag\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Pagination;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Field;
use Ideal\Structure\User;

/**
 * Class ModelAbstract
 * @package Ideal\Structure\Tag\Site
 */
class ModelAbstract extends \Ideal\Structure\Part\Site\ModelAbstract
{
    /** @var bool Флаг отображения списка тегов (false), или списка элементов, которым присвоен тег (true) */
    protected $countSql = false;
    /**
     * @var int
     */
    protected $countElements;

    /**
     * Определение страницы по URL
     *
     * Если передаётся один URL, то выводится только один тэг. Дополнительные тэги передаются через параметр tags,
     * через запятую.
     *
     * @param array $path Разобранная часть URL
     * @param array $url Оставшаяся, неразобранная часть URL
     * @return $this
     */
    public function detectPageByUrl($path, $url)
    {
        parent::detectPageByUrl($path, $url);

        $request = new Request();
        $request->action = 'detail';

        return $this;
    }

    /**
     * Получение списка элементов, которым присвоен тег (в случае, если тег присваивается нескольким структурам)
     *
     * @param int $page Номер отображаемой страницы списка
     * @param string $fieldNames Перечень извлекаемых полей, общих для всех структур
     * @param string $orderBy Поле, присутствующее во всех структурах, по которому проводится сортировка списка
     * @return array Список элементов, которым присвоен тег из $this->pageData
     * @throws \Exception
     */
    public function getElements($page = null, $fieldNames = 'ID,name,url', $orderBy = 'date_create')
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $id = $this->pageData['ID'];
        $tableTag = $config->db['prefix'] . 'ideal_medium_taglist';

        // Считываем все связи этого тега
        $sql = "SELECT * FROM {$tableTag} WHERE tag_id={$id}";
        $listTag = $db->select($sql);

        // Раскладываем айдишники элементов по разделам
        $tables = array();
        foreach ($listTag as $v) {
            $tables[$v['structure_id']][] = $v['part_id'];
        }

        // Построение запросов для извлечения данных из таблиц структур
        $order = (empty($orderBy)) ? '' : ',' . $orderBy;
        $elements = array();
        foreach ($tables as $structureId => $parts) {
            $structure = $config->getStructureById($structureId);
            $tableStructure = $config->getTableByName($structure['structure']);
            $structure = explode('_', $structure['structure']);
            $class = '\\' . $structure[0] . '\\Structure\\' . $structure[1] . '\\Site\\Model';

            // Проверяем нет ли у модели структуры метода для получения элементов привязанных к определённому тегу
            if (method_exists($class, 'tagElementsList')) {
                $classModel = new $class('');
                $structureElementList = $classModel->tagElementsList($parts);
                $elements = array_merge($elements, $structureElementList);
                unset($tables[$structureId]);
            } else {
                $ids = '(' . implode(',', $parts) . ')';
                $sql = "SELECT {$fieldNames}{$order}, '{$class}' as class_name
                      FROM {$tableStructure} WHERE {$tableStructure}.is_active=1 AND {$tableStructure}.ID IN {$ids}";
                $tables[$structureId] = $sql;
            }
        }

        $this->countElements = count($elements);

        // Получаем часть массива для отображения на странице
        $start = ($page > 1) ? ($page - 1) * $this->params['elements_site'] : 0;
        $result = array_slice($elements, $start, $this->params['elements_site']);

        return $result;
    }

    /**
     * Получение листалки для шаблона и стрелок вправо/влево
     *
     * @param string $pageName Название get-параметра, содержащего страницу
     * @return mixed
     */
    public function getElementsPager($pageName)
    {
        // По заданному названию параметра страницы определяем номер активной страницы
        $request = new Request();
        $page = $this->setPageNum($request->{$pageName});

        // Строка запроса без нашего параметра номера страницы
        $query = $request->getQueryWithout($pageName);

        // Определяем кол-во отображаемых элементов на основании названия класса
        $class = strtolower(get_class($this));
        $class = explode('\\', trim($class, '\\'));
        $nameParam = ($class[3] == 'admin') ? 'elements_cms' : 'elements_site';
        $onPage = $this->params[$nameParam];

        $countList = $this->countElements;

        if (($countList > 0) && (ceil($countList / $onPage) < $page)) {
            // Если для запрошенного номера страницы нет элементов - выдать 404
            $this->is404 = true;
            return false;
        }

        $pagination = new Pagination();
        // Номера и ссылки на доступные страницы
        $pager['pages'] = $pagination->getPages($countList, $onPage, $page, $query, $pageName);
        $pager['prev'] = $pagination->getPrev(); // ссылка на предыдущю страницу
        $pager['next'] = $pagination->getNext(); // cсылка на следующую страницу
        $pager['total'] = $countList; // общее количество элементов в списке
        $pager['num'] = $onPage; // количество элементов на странице

        return $pager;
    }

    /**
     * Получение списка элементов, которым присвоен тег (в случае, если тег присваивается одной структуре)
     *
     * @param int $page Номер отображаемой страницы списка
     * @param string $structureName Название структуры, из которой выбираются элементы с указанным тегом
     * @param string $prefix Префикс для формирования URL
     * @return array Список элементов, которым присвоен тег из $this->pageData
     * @throws \Exception
     */
    public function getElementsByStructure($page, $structureName, $prefix)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $id = $this->pageData['ID'];
        $tableTag = $config->db['prefix'] . 'ideal_medium_taglist';
        $structure = $config->getStructureByName($structureName);
        $tableStructure = $config->getTableByName($structureName);

        // Определяем по какому полю нужно проводить сортировку
        $orderBy = '';
        if (isset($structure['params']['field_sort'])
            && $structure['params']['field_sort'] != ''
        ) {
            $orderBy = 'ORDER BY e.' . $structure['params']['field_sort'];
        }

        $this->countSql = "FROM {$tableStructure} AS e
                  INNER JOIN {$tableTag} AS tag ON (tag.part_id = e.ID)
                  WHERE tag.tag_id={$id} AND tag.structure_id={$structure['ID']}";

        $sql = "SELECT e.* " . $this->countSql . $orderBy . $this->getSqlLimit($page);

        $result = $db->select($sql);

        // Формируем правильные ссылки
        $url = new \Ideal\Field\Url\Model();
        foreach ($result as $k => $v) {
            $result[$k]['link'] = $url->getUrlWithPrefix($v, $prefix);
        }

        return $result;
    }

    /**
     * Построение LIMIT части sql-запроса
     *
     * @param int $page Номер отображаемой страницы
     * @return string LIMIT часть sql-запроса (например 'LIMIT 10, 10'
     */
    protected function getSqlLimit($page)
    {
        if (is_null($page)) {
            $this->setPageNum($page);
            return '';
        }

        // Определяем кол-во отображаемых элементов на основании названия класса
        $class = strtolower(get_class($this));
        $class = explode('\\', trim($class, '\\'));
        $nameParam = ($class[3] == 'admin') ? 'elements_cms' : 'elements_site';
        $onPage = $this->params[$nameParam];

        $page = $this->setPageNum($page);
        $start = ($page - 1) * $onPage;

        $sql = " LIMIT {$start}, {$onPage}";
        return $sql;
    }

    public function getCurrent()
    {
        if (isset($this->pageData)) {
            return $this->pageData;
        } else {
            return false;
        }
    }
}
