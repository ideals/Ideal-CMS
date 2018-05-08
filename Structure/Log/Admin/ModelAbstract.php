<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Log\Admin;

use Ideal\Core\Config;
use Ideal\Core\Db;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    public function getList($page = null)
    {
        $logList = parent::getList($page);

        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'ideal_structure_user';
        $sql = 'SELECT ID, email FROM ' . $table;
        $arr = $db->select($sql);
        foreach ($arr as $item) {
            $arr[$item['ID']] = $item['email'];
        }
        foreach ($logList as &$logListItem) {
            $logListItem['user_id'] = $arr[$logListItem['user_id']];
        }
        return $logList;
    }
}
