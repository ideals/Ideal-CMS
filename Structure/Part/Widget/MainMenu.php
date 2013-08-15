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
        $object = $this->model->object;

        foreach ($menu as $k => $v) {
            $menu[$k]['url'] = 'href="' . $url->getUrl($v) . '"';

            // Определяем активен ли данный пункт меню
            $menu[$k]['isActivePage'] = 0;
            if (isset($path[1]['ID']) and ($v['ID'] == $path[1]['ID'])) {
                if (($object['ID'] == $v['ID']) AND isset($object['lvl']) AND ($object['lvl'] == 1)
                        AND ($object['structure_path'] == $path[1]['structure_path'])) {
                    $menu[$k]['url'] = '';
                }
                $menu[$k]['isActivePage'] = 1;
            }
        }
        return $menu;
    }

}