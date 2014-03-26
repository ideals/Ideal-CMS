<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Medium\SelectList;

use Ideal\Medium\AbstractModel;
use Ideal\Core\Db;

class Model extends AbstractModel
{
    /**
     * Возращает список доступных значений
     * @return array|void
     */
    public function getList()
    {
        $db = Db::getInstance();
        $fieldName = $this->fields['id_children']['fieldName'];
        $fieldID = $this->fields['id_children']['fieldID'];
        $table = $this->fields['id_children']['from'];
        $_sql = "SELECT {$fieldID},{$fieldName} FROM {$table} ORDER BY cid";
        $result = $db->queryArray($_sql);
        $list = array();
        foreach ($result as $v) {
            $list[$v[$fieldID]] = $v[$fieldName];
        }
        return $list;
    }

    /**
     * Возращает значения для id_children
     * Для получения в where можно поместить "ID IN (SELECT id_children FROM {{ table }} WHERE id_parent={$ID})"
     * изменть нужто только $ID
     * @param array $where
     * @return array
     */
    public function getChildren($where = array())
    {
        $db = Db::getInstance();
        if (count($where) > 0) {
            $where = implode(' AND ', $where);
            $where = str_replace('{{ table }}', $this->_table, $where);
            $where = ' WHERE ' . $where;
        } else {
            $where = '';
        }
        $table = $this->fields['id_children']['from'];
        $_sql = "SELECT name, rgb FROM {$table} {$where} ORDER BY cid";
        $result = $db->queryArray($_sql);
        return $result;
    }

}
