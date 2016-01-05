<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Medium\TagList;

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
        $config = Config::getInstance();
        // Определяем структуру объекта, которому присваиваются теги
        $structure = $config->getStructureByClass(get_class($this->obj));

        $_sql = "DELETE FROM {$this->table} WHERE part_id='{{ objectId }}' AND structure_id='{$structure['ID']}';";
        if (is_array($newValue) && (count($newValue) > 0)) {
            foreach ($newValue as $v) {
                $_sql .= "INSERT INTO {$this->table}
                              SET part_id='{{ objectId }}', tag_id='{$v}', structure_id='{$structure['ID']}';";
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

        $config = Config::getInstance();
        // Определяем структуру объекта, которому присваиваются теги
        $structure = $config->getStructureByClass(get_class($this->obj));

        $db = Db::getInstance();
        $owner = $this->obj->getPageData();

        if (!isset($owner['ID'])) {
            // Если владелец списка ещё не создан, то и выбранных элементов в нём нет
            return array();
        }

        $_sql = "SELECT {$elementsField} FROM {$this->table}
                  WHERE {$ownerField}='{$owner['ID']}' AND structure_id='{$structure['ID']}'";
        $arr = $db->select($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v[$elementsField];
        }

        return $list;
    }
}
