<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

/**
 * Виджет, отображающий первый уровень главного меню
 */

namespace Ideal\Structure\Part\Widget;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field;

class MainMenu extends \Ideal\Core\Widget
{

    public function getData()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $url = new Field\Url\Model();

        // Считываем главное меню
        $par = array(
            'active' => 1,
            'menu' => 0,
            'lvl' => 1
        );
        $table = strtolower($config->db['prefix'] . 'ideal_structure_part');
        $_sql = "SELECT * FROM {$table} WHERE is_active=:active AND is_not_menu=:menu AND lvl=:lvl ORDER BY cid";
        $menu = $db->select($_sql, $par);

        $path = $this->model->getPath();
        $end = end($path);

        foreach ($menu as $k => $v) {
            $menu[$k]['link'] = 'href="' . $url->getUrl($v) . '"';

            // Определяем активен ли данный пункт меню
            $menu[$k]['isActivePage'] = 0;
            if (isset($path[1]['ID']) && ($v['ID'] == $path[1]['ID'])) {
                if (($end['ID'] == $v['ID']) && isset($end['lvl']) && ($end['lvl'] == 1)
                    && ($end['prev_structure'] == $path[1]['prev_structure'])
                ) {
                    $menu[$k]['link'] = '';
                }
                $menu[$k]['isActivePage'] = 1;
            }
        }
        return $menu;
    }
}