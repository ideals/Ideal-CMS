<?php
/**
 * Виджет, отображающий первый уровень главного меню
 */

namespace Ideal\Structure\Part\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;
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
            'is_active' => 1,
            'is_not_menu' => 0,
            'lvl' => 1);
        $table = strtolower($config->db['prefix'] . 'ideal_structure_part');
        $menu = $db->select($table, $par, 'cid');

        $path = $this->model->getPath();
        $end = end($path);

        foreach ($menu as $k => $v) {
            $menu[$k]['link'] = 'href="' . $url->getUrl($v) . '"';

            // Определяем активен ли данный пункт меню
            $menu[$k]['isActivePage'] = 0;
            if (isset($path[1]['ID']) and ($v['ID'] == $path[1]['ID'])) {
                if (($end['ID'] == $v['ID']) AND isset($end['lvl']) AND ($end['lvl'] == 1)
                        AND ($end['prev_structure'] == $path[1]['prev_structure'])) {
                    $menu[$k]['link'] = '';
                }
                $menu[$k]['isActivePage'] = 1;
            }
        }
        return $menu;
    }

}