<?php
namespace Ideal\Addon\PhpFile;

class Model extends \Ideal\Core\Admin\Model
{
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        $mode = explode('\\', get_class($this->parentModel));
        if ($mode[3] == 'Site') {
            // Для фронтенда к контенту добавляется выполнение указанного файла
            if ($this->pageData['php_file'] != '') {
                require DOCUMENT_ROOT . $this->pageData['php_file'];
            }
        }
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
            $this->setPageData($pageData[$addonKey]);
        }
    }
}
