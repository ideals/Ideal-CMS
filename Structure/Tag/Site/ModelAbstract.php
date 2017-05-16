<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Tag\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Structure\User;

/**
 * Class ModelAbstract
 * @package Ideal\Structure\Tag\Site
 */
class ModelAbstract extends \Ideal\Core\Site\Model
{
    /** @var bool Флаг отображения списка тегов (false), или списка элементов, которым присвоен тег (true) */
    protected $countSql = false;

    /**
     * Определение страницы по URL
     *
     * @param array $path Разобранная часть URL
     * @param array $url Оставшаяся, неразобранная часть URL
     * @return $this
     */
    public function detectPageByUrl($path, $url)
    {
        $db = Db::getInstance();

        // Для авторизированных в админку пользователей отображать скрытые страницы
        $user = new User\Model();
        $checkActive = ($user->checkLogin()) ? '' : ' AND is_active=1';

        $_sql = "SELECT * FROM {$this->_table} WHERE BINARY url=:url {$checkActive}";
        $par = array();
        $par['url'] = !empty($url) ? $url[0] : null;
        $par['time'] = time();

        $tags = $db->select($_sql, $par); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($tags[0]['ID'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        if (count($tags) > 1) {
            $c = count($tags);
            Util::addError("В базе несколько ({$c}) тегов с одинаковым url: " . implode('/', $url));
            $tags = array($tags[0]); // оставляем для отображения первую новость
        }

        $tags[0]['structure'] = 'Ideal_Tag';
        $tags[0]['url'] = $url[0];

        $this->path = array_merge($path, $tags);

        $request = new Request();
        $request->action = 'detail';

        return $this;
    }

    /**
     * Получение списка тегов
     *
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page = null)
    {
        $config = Config::getInstance();
        $tags = parent::getList($page);

        $parentUrl = $this->getParentUrl();
        foreach ($tags as $k => $v) {
            $tags[$k]['link'] = $parentUrl . '/' . $v['url'] . $config->urlSuffix;
            $tags[$k]['date_create'] = Util::dateReach($v['date_create']);
        }

        return $tags;
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
    public function getElements($page = null, $fieldNames = 'ID, name, url', $orderBy = 'date_create')
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
            $tables[$v['prev_structure']][] = $v['part_id'];
        }

        // Построение запросов для извлечения данных из таблиц структур
        $paths = array();
        $order = (empty($orderBy)) ? '' : ',' . $orderBy;
        foreach ($tables as $prevStructure => $parts) {
            list($structureId, $id) = explode('-', $prevStructure);
            $structure = $config->getStructureById($structureId);
            $structure = explode('_', $structure['structure']);
            $class = '\\' . $structure[0] . '\\Structure\\' . $structure[1] . '\\Site\\Model';
            /** @var \Ideal\Core\Site\Model $model */
            $model = new $class('');
            $model->setPageDataById($id);
            $path = $model->detectPath();
            $paths[$prevStructure] = $path;
            $data = $model->getPageData();

            $tableStructure = $config->getTableByName($data['structure']);
            $ids = '(' . implode(',', $parts) . ')';
            $sql = "SELECT {$fieldNames}{$order}, '{$prevStructure}' as prev_structure, '{$class}' as class_name
                      FROM {$tableStructure} WHERE is_active=1 AND ID IN {$ids}";
            $tables[$prevStructure] = $sql;
        }

        $orderBy = ($orderBy == '') ? '' : 'ORDER BY ' . $orderBy;
        $this->countSql = '(' . implode(') UNION (', $tables) . ')';

        $sql = $this->countSql . $orderBy . $this->getSqlLimit($page);

        $result = $db->select($sql);

        // Формируем правильные ссылки
        // todo формирование правильных ссылок для разных структур
        /**
         * foreach ($result as $k => $v) {
         * $url = new \Ideal\Field\Url\Model();
         * $result[$k]['link'] = $url->getUrlWithPrefix($v, $prefix);
         * }
         **/

        return $result;
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
     * {@inheritdoc}
     */
    public function getListCount()
    {
        if (empty($this->countSql)) {
            // Нужно отобразить список тэгов, поэтому их количество считаем обычным способом
            return parent::getListCount();
        }

        $db = Db::getInstance();

        // Подсчитываем количество элементов
        $_sql = 'SELECT COUNT(*) FROM (' . $this->countSql . ') as xyz';
        $list = $db->select($_sql);

        return $list[0]['COUNT(*)'];
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
