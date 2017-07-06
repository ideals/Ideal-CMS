<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Medium\CustomerList;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Medium\AbstractModel;

/**
 * Медиум для получения списка групп пользователей
 */
class Model extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        // Получаем данные о редактируемом заказчике
        $pageData = $this->obj->getPageData();

        $list = array(0 => '---');
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'ideal_structure_crm';
        $sql = 'SELECT ID, name FROM ' . $table . ' ORDER BY name, ID ASC';
        $arr = $db->select($sql);
        foreach ($arr as $item) {
            // Исключаем из списка заказчиков, пригодных для объединения, самого редактируемого заказчика
            if ($item['ID'] != $pageData['ID']) {
                $list[$item['ID']] = $item['name'] ? $item['name'] . ' [' . $item['ID'] . ']' : $item['ID'];
            }
        }
        return $list;
    }
}
