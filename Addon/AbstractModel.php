<?php
namespace Ideal\Addon;

use Ideal\Core\Db;

/**
 * Абстрактный класс, реализующий основные методы для семейства классов Addon
 *
 * Аддоны обеспечивают прикрепление к структуре дополнительного содержимого различных типов.
 *
 */
class AbstractModel extends \Ideal\Core\Admin\Model
{

    public function setPageDataByPrevStructure($prevStructure)
    {
        $db = Db::getInstance();

        // Получаем идентификатор таба из группы
        list(, $tabID) = explode('-', $this->fieldsGroup, 2);
        $_sql = "SELECT * FROM {$this->_table} WHERE prev_structure=:ps AND tab_ID=:tid";
        $pageData = $db->select($_sql, array('ps' => $prevStructure, 'tid' => $tabID));
        if (isset($pageData[0]['ID'])) {
            // TODO сделать обработку ошибки, когда по prevStructure ничего не нашлось
            $this->setPageData($pageData[0]);
        }
    }

    public function delete()
    {
        $db = Db::getInstance();
        $db->delete($this->_table)->where('ID=:id', array('id' => $this->pageData['ID']));
        $db->exec();
    }
}
