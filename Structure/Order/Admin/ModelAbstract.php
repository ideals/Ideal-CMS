<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Order\Admin;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{

    public function getToolbar()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();

        // Ищем всех заказчиков для составления фильтра
        $_table = $config->db['prefix'] . 'ideal_structure_order';
        $_sql = "SELECT DISTINCT order_type FROM {$_table} ORDER BY order_type";
        $this->types = $db->select($_sql);

        $request = new Request();
        $currentType = '';
        if (isset($request->toolbar['types'])) {
            $currentType = $request->toolbar['types'];
        }

        $select = '<select class="form-control" name="toolbar[types]"><option value="">Не фильтровать</option>';
        foreach ($this->types as $type) {
            $selected = '';
            if ($type['order_type'] === $currentType) {
                $selected = 'selected="selected"';
            }
            $select .= '<option ' . $selected . ' value="' . $type['order_type'] . '">';
            $select .= $type['order_type'] . '</option>';
        }
        $select .= '</select>';

        return $select;
    }


    /**
     * Добавление к where-запросу фильтра по category_id
     * @param string $where Исходная WHERE-часть
     * @return string Модифицированная WHERE-часть, с расширенным запросом, если установлена GET-переменная category
     */
    protected function getWhere($where)
    {
        $request = new Request();
        $currentType = '';
        if (isset($request->toolbar['types'])) {
            $currentType = $request->toolbar['types'];
        }
        if ($currentType != '') {
            $db = DB::getInstance();
            if ($where != '') {
                $where .= ' AND ';
            }
            // Выборка статей, принадлежащих этой категории
            $where .= 'order_type = "' . $db->real_escape_string($currentType) . '" ';
        }
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
