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

class Model extends \Ideal\Core\Site\Model
{
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

    public function getElemTag($prevID = false)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $id = $this->pageData['ID'];
        $tableList = $config->db['prefix'] . 'ideal_medium_tagslist';
        // TODO сделать автоматическое определение тегов и структур где они используются
        if ($prevID === false) {
            $sql = "SELECT * FROM {$tableList} WHERE tag_id={$id}";
            $listTag = $db->select($sql);

            $listStructureID = array();
            foreach ($listTag as $key => $value) {
                if (isset($listStructureID[$value['parent_id']])) {
                    continue;
                }
                $listStructureID[$value['parent_id']] = $value['parent_id'];
            }
            return false;
        } else {
            $sql = "SELECT news.* FROM i_ideal_structure_news AS news
INNER JOIN i_ideal_medium_tagslist AS tag ON (tag.news_id = news.ID)
WHERE tag.tag_id={$id} ORDER BY news.date_create DESC";
            $result = $db->select($sql);
        }
        foreach ($result as $k => $v) {
            $result[$k]['date_create'] = Util::dateReach($v['date_create']);
        }
        return $result;
    }
}
