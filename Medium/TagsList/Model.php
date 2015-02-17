<?php
namespace Ideal\Medium\TagsList;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Medium\AbstractModel;

class Model extends AbstractModel
{
    protected $obj;
    protected $fieldName;

    public function getList()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'ideal_structure_tag';
        $sql = 'SELECT ID, name FROM ' . $table . ' ORDER BY name ASC';
        $arr = $db->select($sql);

        $list = array();
        foreach ($arr as $item) {
            $list[$item['ID']] = $item['name'];
        }

        return $list;
    }


    public function getVariants()
    {
        $db = Db::getInstance();
        $pageData = $this->obj->getPageData();
        $newsId = $pageData['ID'];
        $_sql = "SELECT tag_id FROM {$this->table} WHERE part_id='{$newsId}'";
        $arr = $db->select($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v['tag_id'];
        }

        return $list;
    }


    public function getSqlAdd($newValue = array())
    {
        $_sql = "DELETE FROM {$this->table} WHERE part_id='{{ objectId }}';";
        if (is_array($newValue) && (count($newValue) > 0)) {
            foreach ($newValue as $v) {
                $_sql .= "INSERT INTO {$this->table} SET part_id='{{ objectId }}', tag_id='{$v}';";
            }
        }
        return $_sql;
    }
}
