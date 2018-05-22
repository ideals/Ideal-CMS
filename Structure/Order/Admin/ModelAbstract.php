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

        // Ищем все контактные лица для составления фильтра
        $contactPersonTable = $config->getTableByName('Ideal_ContactPerson');
        $_sql = "
          SELECT 
            e.ID, 
            e.name
          FROM 
            {$contactPersonTable} as e 
          ORDER BY e.name, e.ID";
        $contactPersons = $db->select($_sql);

        $request = new Request();
        $currentContactPerson = '';
        if (isset($request->toolbar['contact_person'])) {
            $currentContactPerson = $request->toolbar['contact_person'];
        }

        $select = '<select class="form-control" name="toolbar[contact_person]">';
        $select .= '<option value="">Не фильтровать</option>';
        foreach ($contactPersons as $contactPerson) {
            $selected = '';
            if ($contactPerson['ID'] == $currentContactPerson) {
                $selected = 'selected="selected"';
            }
            $select .= '<option ' . $selected . ' value="' . $contactPerson['ID'] . '">';
            $select .= $contactPerson['name'] . ' ['. $contactPerson['ID'].']</option>';
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
        if (isset($request->toolbar['contact_person'])) {
            $currentLead = $request->toolbar['contact_person'];
        }
        if ($currentLead != '') {
            $db = DB::getInstance();
            if ($where != '') {
                $where .= ' AND';
            }
            // Выборка заказов, принадлежащих этому лиду
            $where .= ' contact_person =' . $db->real_escape_string($currentLead);
        }
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
