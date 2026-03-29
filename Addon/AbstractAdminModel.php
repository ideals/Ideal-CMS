<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon;

use Ideal\Core\Admin\Model;
use Ideal\Core\Db;

/**
 * Абстрактный класс, реализующий основные методы для семейства классов Addon в админской части
 *
 * Аддоны обеспечивают прикрепление к структуре дополнительного содержимого различных типов.
 *
 */
class AbstractAdminModel extends Model
{
    /**
     * {@inheritdoc}
     */
    public function delete(): void
    {
        $db = Db::getInstance();
        $db->delete($this->_table)->where('ID=:id', ['id' => $this->pageData['ID']]);
        $db->exec();
    }

    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);
        return $this->pageData;
    }

    public function setPageDataByPrevStructure($prevStructure): void
    {
        $db = Db::getInstance();

        // Получаем идентификатор таба из группы
        [, $tabID] = explode('-', $this->fieldsGroup, 2);
        $_sql = sprintf('SELECT * FROM %s WHERE prev_structure=:ps AND tab_ID=:tid', $this->_table);
        $pageData = $db->select($_sql, ['ps' => $prevStructure, 'tid' => $tabID]);
        if (isset($pageData[0]['ID'])) {
            // TODO сделать обработку ошибки, когда по prevStructure ничего не нашлось
            /** @noinspection PhpUndefinedMethodInspection */
            $this->setPageData($pageData[0]);
        }
    }
}
