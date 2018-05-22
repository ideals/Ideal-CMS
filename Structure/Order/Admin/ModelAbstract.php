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

        // Ищем всех лидов для составления фильтра
        $leadtable = $config->getTableByName('Ideal_Lead');
        $contactPersonTable = $config->getTableByName('Ideal_ContactPerson');
        $_sql = "
          SELECT 
            e.ID, 
            e.name as leadName,
            cps.name as cpsLeadName 
          FROM 
            {$leadtable} as e 
          LEFT JOIN {$contactPersonTable} as cps 
          ON e.ID = cps.lead
          GROUP BY e.ID
          ORDER BY e.name, e.ID";
        $leads = $db->select($_sql);

        $request = new Request();
        $currentLead = '';
        if (isset($request->toolbar['lead'])) {
            $currentLead = $request->toolbar['lead'];
        }

        $select = '<select class="form-control" name="toolbar[lead]"><option value="">Не фильтровать</option>';
        foreach ($leads as $lead) {
            if ($lead['name']) {
                $name = $lead['leadName'];
            } else {
                $name = $lead['cpsLeadName'];
            }
            $selected = '';
            if ($lead['ID'] == $currentLead) {
                $selected = 'selected="selected"';
            }
            $select .= '<option ' . $selected . ' value="' . $lead['ID'] . '">';
            $select .= $name . ' ['. $lead['ID'].']</option>';
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
        $currentLead = '';
        if (isset($request->toolbar['lead'])) {
            $currentLead = $request->toolbar['lead'];
        }
        if ($currentLead != '') {
            $db = DB::getInstance();
            if ($where != '') {
                $where .= ' AND ';
            }
            // Выборка заказов, принадлежащих этому лиду
            $where .= 'lead =' . $db->real_escape_string($currentLead);
        }
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
