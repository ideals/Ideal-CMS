<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Lead\Admin;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Structure\Lead\LeadFilter;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    /** @var mixed null - если фильтр не установлен, Объект фильтра если фильтр был применён */
    public $filter = null;

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        $this->filter = new LeadFilter();
    }

    public function saveElement($result, $groupName = 'general')
    {
        $result = $this->clearFields($result, $groupName);
        return parent::saveElement($result, $groupName);
    }


    public function createElement($result, $groupName = 'general')
    {
        $result = $this->clearFields($result, $groupName);
        return parent::saveElement($result, $groupName);
    }

    public function getList($page = null)
    {
        $this->filter->setLeadModel($this);
        return parent::getList($page);
    }

    public function setPageDataById($id)
    {
        parent::setPageDataById($id);
        $pageData = $this->getPageData();

        // Если у лида нет собственного назания, то подставляем ему название из первого контактного лица
        if (!$pageData['name']) {
            $config = Config::getInstance();
            $contactPersonStructure = $config->getStructureByName('Ideal_ContactPerson');
            if ($contactPersonStructure) {
                $config = Config::getInstance();
                $db = Db::getInstance();
                $leadStructure = $config->getStructureByName('Ideal_Lead');
                $contactPersonAddonTable = $config->getTableByName('Ideal_ContactPerson', 'Addon');
                $contactPersonTable = $config->getTableByName('Ideal_ContactPerson');

                $sql = "SELECT cp.* FROM {$contactPersonAddonTable} as cpa";
                $sql .= " LEFT JOIN {$contactPersonTable} as cp ON cp.ID = cpa.contact_person";
                $sql .= " WHERE cpa.prev_structure = CONCAT_WS('-', {$leadStructure['ID']}, {$pageData['ID']})";
                $sql .= " ORDER BY cpa.tab_ID LIMIT 1";
                $contactPersons = $db->select($sql);
                if ($contactPersons) {
                    $pageData['name'] = $contactPersons[0]['name'];
                    $this->setPageData($pageData);
                }
            }
        }
    }

    /**
     * Убирает поля с внешними данными из сохранения
     *
     * @param $result array Данные с формы
     * @param $groupName string Нсзвание группы полей
     * @return array очищенный от полей которые не нужно записывать в базу
     */
    private function clearFields($result, $groupName)
    {
        if (isset($result['items'][$groupName . '_cpName'])) {
            unset($result['items'][$groupName . '_cpName']);
        }
        if (isset($result['items'][$groupName . '_lastInteraction'])) {
            unset($result['items'][$groupName . '_lastInteraction']);
        }
        if (isset($result['items'][$groupName . '_cpPhone'])) {
            unset($result['items'][$groupName . '_cpPhone']);
        }
        if (isset($result['items'][$groupName . '_cpEmail'])) {
            unset($result['items'][$groupName . '_cpEmail']);
        }
        return $result;
    }
}
