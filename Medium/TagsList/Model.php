<?php
namespace Ideal\Medium\TagsList;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Medium\AbstractModel;

class Model extends AbstractModel
{

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getSqlAdd($newValue = array())
    {
        $_sql = "DELETE FROM {$this->table} WHERE part_id='{{ objectId }}';";
        $config = Config::getInstance();
        $structureID = $this->obj->getStructureFullName();
        $structureID = $config->getStructureByName($structureID);
        $structureID = $structureID['ID'];
        $structureID = "parent_id = '{$structureID}'";
        $_sql = trim($_sql, ';') . ' AND ' . $structureID . ';';
        $structureID = ', ' . $structureID;
        if (is_array($newValue) && (count($newValue) > 0)) {
            foreach ($newValue as $v) {
                $_sql .= "INSERT INTO {$this->table} SET part_id='{{ objectId }}', tag_id='{$v}' {$structureID};";
            }
        }
        return $_sql;
    }
}
