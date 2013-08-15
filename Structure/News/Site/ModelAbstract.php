<?php
namespace Ideal\Structure\News\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;
use Ideal\Core\Util;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    public $cid;


    /**
     * @param int $page Номер отображаемой страницы
     * @param int $onPage
     * @return array Полученный список элементов
     */
    public function getList($page, $onPage)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        $start = ($page < 2) ? 0 : ($page - 1) * $onPage;

        $orderBy = $this->params['field_sort'];
        $orderBy = ($orderBy == '') ? '' : 'ORDER BY ' . $orderBy;

        // Считываем новости из базы
        $structurePath = $this->object['structure_path'] . '-' . $this->object['ID'];
        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1 AND structure_path='{$structurePath}'"
              . ' AND date_create < ' . time() . " {$orderBy} LIMIT {$start}, {$onPage}";

        $news = $db->queryArray($_sql);

        $parentUrl = $this->getParentUrl();
        foreach ($news as $k => $v) {
            if ($v['content'] == '') {
                $news[$k]['link'] = '';
            } else {
                $news[$k]['link'] = $parentUrl . '/' . $v['url'] . $config->urlSuffix;
            }
            $news[$k]['date_create'] = Util::dateReach($v['date_create']);
        }

        return $news;
    }


    /**
     * Получить общее количество элементов в списке
     * @return array Полученный список элементов
     */
    public function getListCount()
    {
        /* @var Db $db */
        $db = Db::getInstance();

        $_sql = "SELECT COUNT(ID) FROM {$this->_table} WHERE is_active=1
                    AND structure_path='{$this->structurePath}'  AND date_create < " . time();

        $list = $db->queryArray($_sql);

        return $list[0]['COUNT(ID)'];
    }


    public function detectPageByUrl($url, $path)
    {
        $db = Db::getInstance();

        $url = mysql_real_escape_string($url[0]);
        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1 AND url='{$url}'  AND date_create < " . time();

        $news = $db->queryArray($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($news[0]['ID'])) {
            return '404';
        }

        $news[0]['structure'] = 'Ideal_News';
        $news[0]['url'] = $url;

        $this->path = array_merge($path, $news);

        $request = new Request();
        $request->action = 'detail';

        return array();
    }


    public function getText()
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        if (isset($this->object['content'])) {
            $text = $this->object['content'];
        } else {
            // TODO проработать ситуацию, когда текст в шаблоне (сейчас нет определения модуля)
            $table = $config->db['prefix'] . 'Template_' . $this->object['template'];
            $structurePath = $this->object['structure_path'] . '-' . $this->object['ID'];
            $text = $db->select($table, $structurePath, '', 'structure_path');
            $text = $text[0]['content'];
        }

        $header = '';
        if (preg_match('/<h1>(.*)<\/h1>/isU', $text, $header)) {
            $text = preg_replace('/<h1>(.*)<\/h1>/isU', '', $text, 1);
            $this->header = $header[1];
        }
        return $text;
    }


    public function setObjectNew()
    {

    }


    public function getStructureElements()
    {
        $list = $this->getList(0, 9999);
        return $list;
    }

}