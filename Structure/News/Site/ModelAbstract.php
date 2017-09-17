<?php
namespace Ideal\Structure\News\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Structure\User;

class ModelAbstract extends \Ideal\Core\Site\Model
{

    public $cid;

    public function detectPageByUrl($path, $url)
    {
        $db = Db::getInstance();

        // Для авторизированных в админку пользователей отображать скрытые страницы
        $user = new User\Model();
        $checkActive = ($user->checkLogin()) ? '' : ' AND is_active=1';

        $_sql = "SELECT * FROM {$this->_table} WHERE BINARY url=:url {$checkActive} AND date_create < :time";
        $par = array('url' => $url[0], 'time' => time());

        $news = $db->select($_sql, $par); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($news[0]['ID'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        if (count($news) > 1) {
            $c = count($news);
            Util::addError("В базе несколько ({$c}) новостей с одинаковым url: " . implode('/', $url));
            $news = array($news[0]); // оставляем для отображения первую новость
        }

        $news[0]['structure'] = 'Ideal_News';
        $news[0]['url'] = $url[0];

        $this->path = array_merge($path, $news);

        $request = new Request();
        $request->action = 'detail';

        return $this;
    }

    /**
     * Возвращающет список всех новостей
     *
     * Этот метод используется в построении html-карты сайта на основе БД
     *
     * @return array Список вложенных элементов
     */
    public function getStructureElements()
    {
        $list = $this->getList();
        return $list;
    }

    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page = null)
    {
        $config = Config::getInstance();
        $news = parent::getList($page);

        $parentUrl = $this->getParentUrl();
        foreach ($news as $k => $v) {
            if (!isset($v['content']) || ($v['content'] == '')) {
                $news[$k]['link'] = '';
            } else {
                $news[$k]['link'] = $parentUrl . '/' . $v['url'] . $config->urlSuffix;
            }
            $news[$k]['date_create'] = Util::dateReach($v['date_create']);
        }

        return $news;
    }

    public function getText()
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $end = end($this->path);

        if (isset($end['content']) && !empty($end['content'])) {
            $text = $end['content'];
        } elseif (empty($end['content']) && !empty($end['addon'])) {
            $text = '';
            $addons = json_decode($end['addon']);
            foreach ($config->structures as $key => $value) {
                if ($value['structure'] == $end['structure']) {
                    $prevStructure = $value['ID'] . '-' . $end['ID'];
                }
            }
            foreach ($addons as $addon) {
                $addonGroupName = strtolower(end(explode('_', $addon[1])));
                $table = $config->db['prefix'] . 'ideal_addon_' . $addonGroupName;
                $_sql = "SELECT * FROM {$table} WHERE prev_structure=:ps AND tab_ID=:ti";
                $result = $db->select($_sql, array('ps' => $prevStructure, 'ti' => $addon[0]));
                $text .= $result[0]['content'];
            }
        } else {
            $text = '';
        }

        $header = '';
        if (preg_match('/<h1.*>(.*)<\/h1>/isU', $text, $header)) {
            $text = preg_replace('/<h1>(.*)<\/h1>/isU', '', $text, 1);
            $this->header = $header[1];
        }
        if (count($header) > 1) {
            $text = str_replace($header[0], '', $text);
        }
        return $text;
    }

    public function getWhere($where)
    {
        $where = 'WHERE ' . $where . ' AND is_active=1 AND date_create < ' . time();
        return $where;
    }
}
