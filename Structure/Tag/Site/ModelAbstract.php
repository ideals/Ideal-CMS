<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
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

        $_sql = "SELECT * FROM {$this->_table} WHERE url=:url {$checkActive}";
        $par = array('url' => $url[0], 'time' => time());

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
     * @param string $fieldNames Перечень извлекаемых полей, общих для всех структур
     * @param string $orderBy Поле, присутствующее во всех структурах, по которому проводится сортировка списка
     * @return array Список элементов, которым присвоен тег из $this->pageData
     * @throws \Exception
     */
    public function getElements($fieldNames = 'ID, name, url', $orderBy = 'date_create')
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $id = $this->pageData['ID'];
        $tableTag = $config->db['prefix'] . 'ideal_medium_tagslist';

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
        foreach ($tables as $structureId => $parts) {
            $structure = $config->getStructureById($structureId);
            $tableStructure = $config->getTableByName($structure['structure']);
            $ids = '(' . implode(',', $parts) . ')';
            $sql = "SELECT {$fieldNames}{$order} FROM {$tableStructure} WHERE is_active=1 AND ID IN {$ids}";
            $tables[$structureId] = $sql;
        }

        $orderBy = ($orderBy == '') ? '' : 'ORDER BY ' . $orderBy;

        $sql = '(' . implode(') UNION (', $tables) . ')' . $orderBy;
        $result = $db->select($sql);

        return $result;
    }

    /**
     * Получение списка элементов, которым присвоен тег (в случае, если тег присваивается одной структуре)
     *
     * @param string $structureName Название структуры, из которой выбираются элементы с указанным тегом
     * @return array Список элементов, которым присвоен тег из $this->pageData
     * @throws \Exception
     */
    public function getElementsByStructure($structureName)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $id = $this->pageData['ID'];
        $tableTag = $config->db['prefix'] . 'ideal_medium_tagslist';
        $structure = $config->getStructureByName($structureName);
        $tableStructure = $config->getTableByName($structureName);

        // Определяем по какому полю нужно проводить сортировку
        $orderBy = '';
        if (isset($structure['params']['field_sort'])
            && $structure['params']['field_sort'] != ''
        ) {
            $orderBy = 'ORDER BY e.' . $structure['params']['field_sort'];
        }

        $sql = "SELECT e.* FROM {$tableStructure} AS e
                  INNER JOIN {$tableTag} AS tag ON (tag.part_id = e.ID)
                  WHERE tag.tag_id={$id} AND tag.structure_id={$structure['ID']} {$orderBy}";
        $result = $db->select($sql);

        return $result;
    }
}
