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
use Ideal\Structure\ContactPerson\Model as ContactPersonModel;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    /**
     * Пытается установить контактное лицо для заказа
     * @throws \Exception
     */
    public function setContactPerson()
    {
        // По данным из заказа пытаемся связать его с определённым контактным лицом
        $pageData = $this->getPageData();

        // Если у заказа уже есть контактное лицо, то ничего делать не нужно
        if (!$pageData['contact_person']) {
            $contactPersonModel = new ContactPersonModel();
            $contactPersonId = $contactPersonModel->setEmail($pageData['email'])
                ->setClientId(isset($pageData['client_id']) ? $pageData['client_id'] : '')
                ->setName(isset($pageData['name']) ? $pageData['name'] : '')
                ->setPhone(isset($pageData['phone']) ? $pageData['phone'] : '')
                ->getContactPersonId();

            // Если нашли контактное лицо, то заказ привязываем к нему
            if (false !== $contactPersonId) {
                $pageData['contact_person'] = $contactPersonId;
                $this->setPageData($pageData);

                // Записываем соотнесение контактного лица с заказом в базу
                $db = Db::getInstance();
                $values = array('contact_person' => $contactPersonId);
                $sql = 'ID = :ID';
                $params = array('ID' => $pageData['ID']);
                $db->update($this->_table)->set($values)->where($sql, $params)->exec();
            } else {
                $pageData['contact_person'] = 0;
            }
        }
    }

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
