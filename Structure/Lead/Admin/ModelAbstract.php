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
     * Получает список заказов для лида
     *
     * @return array Список заказов
     * @throws \Exception
     */
    public function getLeadOrders()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $contactPersonAddonTable = $config->getTableByName('Ideal_ContactPerson', 'Addon');
        $contactPersonStructureTable = $config->getTableByName('Ideal_ContactPerson');
        $orderStructureTable = $config->getTableByName('Ideal_Order');
        $structure = $config->getStructureByClass(get_class($this));
        $pageData = $this->getPageData();
        $prevStructure = $structure['ID'] . '-' . $pageData['ID'];
        $sql = <<<SQL
          SELECT
           isl.*,
           iscp.name as cpName,
           iscp.email as cpEmail,
           iso.content as orderContent,
           iso.price as orderPrice,
           iso.ID as orderId
          FROM
           {$this->_table} as isl
          LEFT JOIN {$contactPersonAddonTable} as iacp
          ON iacp.prev_structure = '{$prevStructure}' 
          LEFT JOIN {$contactPersonStructureTable} as iscp
          ON iscp.ID = iacp.contact_person
          LEFT JOIN {$orderStructureTable} as iso
          ON iso.contact_person = iscp.ID
          WHERE isl.ID = {$pageData['ID']}
          GROUP BY iso.ID
SQL;

        return $db->select($sql);
    }
}
