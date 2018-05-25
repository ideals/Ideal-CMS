<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Medium\ContactPerson;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Medium\AbstractModel;

/**
 * Медиум для получения списка контактных лиц
 */
class Model extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        // Получаем список контактных лиц
        $config = Config::getInstance();
        $db = Db::getInstance();
        $contactPersonTable = $config->getTableByName('Ideal_ContactPerson');
        $contactPersonList = $db->select('SELECT * FROM ' . $contactPersonTable);

        if (is_a($this->obj, '\Ideal\Structure\Order\Admin\ModelAbstract')) {
            $list = array('0' => 'Контактное лицо не выбрано');
        } else {
            $list = array('0' => 'Новое контактное лицо');
        }
        foreach ($contactPersonList as $contactPerson) {
            $listName = array($contactPerson['name'], $contactPerson['email'], $contactPerson['phone']);
            $listName = array_filter($listName, function ($v) {
                if (is_string($v)) {
                    $v = trim($v);
                }
                return (bool)$v;
            });
            $list[$contactPerson['ID']] = implode(' - ', $listName);
        }
        return $list;
    }
}
