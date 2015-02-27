<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Medium\TagsList;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Medium\AbstractModel;

/**
 * Получение и сохранение связей между элементами структур и тегами
 */
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
        $prevStructure = $this->obj->getPrevStructure();
        $_sql = "DELETE FROM {$this->table} WHERE part_id='{{ objectId }}' AND prev_structure='{$prevStructure}';";
        if (is_array($newValue) && (count($newValue) > 0)) {
            foreach ($newValue as $v) {
                $_sql .= "INSERT INTO {$this->table}
                              SET part_id='{{ objectId }}', tag_id='{$v}', prev_structure='{$prevStructure}';";
            }
        }
        return $_sql;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        $fieldNames = array_keys($this->fields);
        $ownerField = $fieldNames[0];
        $elementsField = $fieldNames[1];

        $prevStructure = $this->obj->getPrevStructure();

        $db = Db::getInstance();
        $owner = $this->obj->getPageData();
        $_sql = "SELECT {$elementsField} FROM {$this->table}
                  WHERE {$ownerField}='{$owner['ID']}' AND prev_structure='{$prevStructure}'";
        $arr = $db->select($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v[$elementsField];
        }

        return $list;
    }
}
