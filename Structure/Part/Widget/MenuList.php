<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Part\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field;

/**
 * Виджет для получение иерархии страниц заданной вложенности
 *
 * Пример использования:
 *
 *     $hierarchy = new MenuList($model);
 *     $hierarchy->setLvl(4);
 *     $vars['hierarchy'] = $hierarchy->getData();
 */
class MenuList extends \Ideal\Core\Widget
{
    /** @var int Уровень вложенности, до которого выбираются страницы */
    protected $lvl = 4;

    /** @var array Массив, позволяющий избежать получения из БД страниц, если они были получены вне виджета */
    protected $menuList = array();

    /**
     * Получение списка страниц
     *
     * @return array Список страниц
     */
    public function getData()
    {
        $menuList = $this->getList();

        $path = $this->model->getPath();
        $object = array_pop($path);
        $digits = (isset($this->model->params['digits'])) ? $this->model->params['digits'] : 3;
        $smallCidActive = isset($object['cid']) ? $object['cid'] : '';

        $lvl = 1;
        $config = Config::getInstance();
        $menuUrl = array('0' => array('url' => $config->structures[0]['url']));
        $url = new Field\Url\Model();

        $menu = array();
        $lvlExit = false;
        foreach ($menuList as $k => $v) {
            if ($v['is_active'] == 0) {
                // Пропускаем неактивный элемент и ставим флаг для пропуска вложенных элементов
                $lvlExit = $v['lvl'];
                unset($menuList[$k]);
                continue;
            }
            if ($lvlExit !== false && $v['lvl'] > $lvlExit) {
                // Если это элемент, вложенный в скрытый, то не включаем его в список вывода
                unset($menuList[$k]);
                continue;
            }
            $lvlExit = false;

            $menu[$k] = $v;
            if ($v['lvl'] > $lvl) {
                if ($v['url'] != '/') {
                    $menuUrl[] = $menuList[$k - 1];
                }
                $url->setParentUrl($menuUrl);
            } elseif ($v['lvl'] < $lvl) {
                $menuUrl = array_slice($menuUrl, 0, ($v['lvl'] - $lvl));
                $url->setParentUrl($menuUrl);
            }
            $lvl = $v['lvl'];

            // Определяем активен ли данный пункт меню
            $menu[$k]['isActivePage'] = 0;
            $currentCid = substr($v['cid'], 0, $v['lvl'] * $digits);
            if (isset($object['lvl']) && $object['lvl'] >= $lvl
                && substr($smallCidActive, 0, strlen($currentCid)) == $currentCid
            ) {
                $menu[$k]['isActivePage'] = 1;
            }
            if (isset($v['is_skip']) && $v['is_skip'] == 1 && $v['url'] == '---') {
                // Для этого элемента ссылку делать не надо
                $menu[$k]['link'] = '';
            }
            if (isset($v['url_full']) && $v['url_full'] != '') {
                $menu[$k]['link'] = $v['url_full'];
            } else {
                $menu[$k]['link'] = $this->prefix . $url->getUrl($v) . $this->query;
            }
        }
        $pageList = $this->getSubPages($menu);
        return $pageList;
    }

    /**
     * Рекурсивный метод для построения иерархии вложенных страниц
     *
     * @param array $menu Массив, в котором строится иерархия
     * @return array Массив с построенной иерархией дочерних элементов
     */
    protected function getSubPages(&$menu)
    {
        // Записываем в массив первый элемент
        $pageList = array(
            array_shift($menu)
        );

        $prev = $pageList[0]['lvl'];

        while (count($menu) != 0) {
            $m = reset($menu);
            if ($m['lvl'] == $prev) {
                $pageList[] = array_shift($menu);
                $prev = $m['lvl'];
            } elseif ($m['lvl'] > $prev) {
                end($pageList);
                $key = key($pageList);
                $pageList[$key]['subPageList'] = $this->getSubPages($menu);
            } else {
                return $pageList;
            }
        }
        return $pageList;

    }

    /**
     * Получение модели страницы с данными
     *
     * @return \Ideal\Core\Site\Model Модель страницы с данными
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Выполняет запрос к БД для получения списка страниц
     *
     * Метод сделан максимально просто, чтобы было легче модифицировать получение
     * страниц в наследниках виджета.
     *
     * @return array Список страниц из БД
     */
    public function getList()
    {
        if (!empty($this->menuList)) {
            return $this->menuList;
        }

        $db = Db::getInstance();
        $config = Config::getInstance();

        // Считываем список страниц
        $table = $config->db['prefix'] . 'ideal_structure_part';
        $_sql = "SELECT *
                 FROM {$table}
                 WHERE is_active=1 AND lvl<{$this->lvl}
                 ORDER BY cid";
        $menuList = $db->select($_sql);

        return $menuList;
    }

    /**
     * Установка уровня вложенности для выборки страниц
     *
     * @param int $lvl Уровень вложенности, до которого выбираются страницы
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;
    }

    /**
     * Метод позволяет задать список страниц, если он уже был определён вне виджета
     *
     * @param array $menuList Массив с плоским списком страниц
     */
    public function setMenuList($menuList)
    {
        $this->menuList = $menuList;
    }
}
