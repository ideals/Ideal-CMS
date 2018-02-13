<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\DataList\Admin;

use Ideal\Core\Db;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{

    /**
     * Определение справочника по $parentUrl
     *
     * @param $parentUrl
     * @return array
     */
    public function getByParentUrl($parentUrl)
    {
        $db = Db::getInstance();
        $_sql = "SELECT * FROM {$this->_table} WHERE parent_url='{$parentUrl}'";
        $arr = $db->select($_sql);
        if (!isset($arr[0]['ID'])) {
            $arr[0] = array();
        }
        return $arr[0];
    }
}
