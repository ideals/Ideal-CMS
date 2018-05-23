<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Order\Admin;

use Ideal\Core\Db;
use Ideal\Core\Config;
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

        $selectContactPerson = '<label>Контактное лицо</label>&nbsp;';
        $selectContactPerson .= '<select class="form-control" name="toolbar[contact_person]">';
        $selectContactPerson .= '<option value="">Не фильтровать</option>';
        foreach ($contactPersons as $contactPerson) {
            $selected = '';
            if ($contactPerson['ID'] == $currentContactPerson) {
                $selected = 'selected="selected"';
            }
            $selectContactPerson .= '<option ' . $selected . ' value="' . $contactPerson['ID'] . '">';
            $selectContactPerson .= $contactPerson['name'] . ' ['. $contactPerson['ID'].']</option>';
        }
        $selectContactPerson .= '</select>';

        // Ищем все лиды для составления фильтра
        $leadTable = $config->getTableByName('Ideal_Lead');
        $_sql = "
          SELECT 
            e.ID, 
            e.name
          FROM 
            {$leadTable} as e 
          ORDER BY e.name, e.ID";
        $leads = $db->select($_sql);

        $request = new Request();
        $currentLead = '';
        if (isset($request->toolbar['lead'])) {
            $currentLead = $request->toolbar['lead'];
        }

        $selectLead = '&nbsp;&nbsp;<label>Лид</label>&nbsp;';
        $selectLead .= '<select class="form-control" name="toolbar[lead]">';
        $selectLead .= '<option value="">Не фильтровать</option>';
        foreach ($leads as $lead) {
            $selected = '';
            if ($lead['ID'] == $currentLead) {
                $selected = 'selected="selected"';
            }
            $selectLead .= '<option ' . $selected . ' value="' . $lead['ID'] . '">';
            $selectLead .= $lead['name'] . ' ['. $lead['ID'].']</option>';
        }
        $selectLead .= '</select>';

        return $selectContactPerson . $selectLead;
    }


    /**
     * Добавление к where-запросу фильтра по контактному лицу и/или лиду
     * @param string $where Исходная WHERE-часть
     * @return string Изменённая WHERE-часть, с расширенным запросом, если установлены соответствующие GET-параметры
     * @throws \Exception
     */
    protected function getWhere($where)
    {
        $request = new Request();
        $config = Config::getInstance();
        $db = DB::getInstance();

        $currentContactPerson = '';
        if (isset($request->toolbar['contact_person'])) {
            $currentContactPerson = $request->toolbar['contact_person'];
        }

        $currentLead = '';
        if (isset($request->toolbar['lead'])) {
            $currentLead = $request->toolbar['lead'];
        }

        if ($currentContactPerson != '') {
            if ($where != '') {
                $where .= ' AND';
            }
            // Выборка заказов, принадлежащих этому контатномц лицу
            $where .= ' e.contact_person =' . $db->real_escape_string($currentContactPerson);
        }

        if ($currentLead != '') {
            if ($where != '') {
                $where .= ' AND';
            }
            // Выбор контактных лиц, принадлежащих этому лиду
            $contactPersonTable = $config->getTableByName('Ideal_ContactPerson');
            $addSql = 'SELECT cps.ID FROM ' . $contactPersonTable . ' as cps WHERE cps.lead = ';
            $addSql .= $db->real_escape_string($currentLead);

            // Выборка заказов, принадлежащих этому лиду
            $where .= ' e.contact_person IN (' . $addSql . ')';
        }

        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
