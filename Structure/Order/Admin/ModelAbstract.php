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
        $_table = $config->db['prefix'] . 'ideal_structure_crm';
        $_sql = "SELECT ID, name FROM {$_table} ORDER BY name, ID";
        $this->customers = $db->select($_sql);

        $request = new Request();
        $currentCustomer = '';
        if (isset($request->toolbar['customer'])) {
            $currentCustomer = $request->toolbar['customer'];
        }

        $select = '<select class="form-control" name="toolbar[customer]"><option value="">Не фильтровать</option>';
        foreach ($this->customers as $customer) {
            $selected = '';
            if ($customer['ID'] == $currentCustomer) {
                $selected = 'selected="selected"';
            }
            $select .= '<option ' . $selected . ' value="' . $customer['ID'] . '">';
            $select .= $customer['name'] . ' ['. $customer ['ID'].']</option>';
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
        $currentCustomer = '';
        if (isset($request->toolbar['customer'])) {
            $currentCustomer = $request->toolbar['customer'];
        }
        if ($currentCustomer != '') {
            $db = DB::getInstance();
            if ($where != '') {
                $where .= ' AND ';
            }
            // Выборка статей, принадлежащих этой категории
            $where .= 'customer =' . $db->real_escape_string($currentCustomer);
        }
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
