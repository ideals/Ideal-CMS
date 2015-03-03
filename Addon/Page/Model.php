<?php
namespace Ideal\Addon\Page;

use Ideal\Core\Db;

class Model extends \Ideal\Core\Admin\Model
{

    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);
        return $this->pageData;
    }

    public function setPageDataByPrevStructure($prevStructure)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE prev_structure=:ps";
        $pageData = $db->select($_sql, array('ps' => $prevStructure));
        if (isset($pageData[0]['ID'])) {
            list($group, $addonKey) = explode('_', $this->fieldsGroup, 2);
            $addonKey = intval($addonKey) - 1;

            // Если это новая вкладка, то нужно возвращать значения по умолчанию
            $this->setPageData($pageData[$addonKey]);
        }
    }
}
