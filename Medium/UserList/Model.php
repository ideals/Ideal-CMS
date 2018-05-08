<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Medium\UserList;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Medium\AbstractModel;

/**
 * Медиум для получения списка пользователей системы
 */
class Model extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        $list = array(0 => '---');
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'ideal_structure_user';
        $sql = 'SELECT ID, email FROM ' . $table . ' ORDER BY email ASC';
        $arr = $db->select($sql);
        foreach ($arr as $item) {
            $list[$item['ID']] = $item['email'];
        }
        return $list;
    }
}
