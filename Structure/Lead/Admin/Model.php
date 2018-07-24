<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Lead\Admin;

use Ideal\Core\Config;
use Ideal\Core\Db;

class Model extends ModelAbstract
{
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
