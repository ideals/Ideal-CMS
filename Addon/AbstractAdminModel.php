<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon;

use Ideal\Core\Admin;
use Ideal\Core\Db;

/**
 * Абстрактный класс, реализующий основные методы для семейства классов Addon в админской части
 *
 * Аддоны обеспечивают прикрепление к структуре дополнительного содержимого различных типов.
 *
 */
class AbstractAdminModel extends Admin\Model
{
    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $db = Db::getInstance();
        $db->delete($this->_table)->where('ID=:id', array('id' => $this->pageData['ID']));
        $db->exec();
    }

    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);
        return $this->pageData;
    }

    public function setPageDataByPrevStructure($prevStructure)
    {
        $db = Db::getInstance();

        // Получаем идентификатор таба из группы
        list(, $tabID) = explode('-', $this->fieldsGroup, 2);
        $_sql = "SELECT * FROM {$this->_table} WHERE prev_structure=:ps AND tab_ID=:tid";
        $pageData = $db->select($_sql, array('ps' => $prevStructure, 'tid' => $tabID));
        if (isset($pageData[0]['ID'])) {
            // TODO сделать обработку ошибки, когда по prevStructure ничего не нашлось
            /** @noinspection PhpUndefinedMethodInspection */
            $this->setPageData($pageData[0]);
        }
    }
}
