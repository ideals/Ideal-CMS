<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Tag\Widget;

use Ideal\Core\Request;
use Ideal\Core\Widget;
use Ideal\Core\Config;
use Ideal\Core\Db;

class Tags extends Widget
{
    /** Массив допустимых идентификаторов тегов  */
    protected array $allowedIds = [];

    public static function getTags($id, $structureId)
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'ideal_structure_tag';
        $tableList = $config->db['prefix'] . 'ideal_medium_taglist';
        $sql = "SELECT tags.* FROM {$table} AS tags
                  INNER JOIN {$tableList} AS tag ON (tag.tag_id = tags.ID)
                  WHERE tag.part_id={$id} AND tag.structure_id={$structureId} AND tags.is_active=1";
        return $db->select($sql);
    }


    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        $path = $this->model->getPath();
        $lastPath = array_pop($path);

        // Получаем все теги из базы
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'ideal_structure_tag';

        // Если в настройках модели указано, какая prev_structure должна быть у тегов, берём только такие
        $fields = $this->model->fields;
        $where = '';
        if (isset($fields['tag']['prev_structure'])) {
            $where .= 'WHERE tags.prev_structure = "' . $fields['tag']['prev_structure'] . '"';
        }

        $sql = "SELECT tags.* FROM {$table} AS tags {$where} ORDER BY tags.cid";
        $result = $db->select($sql);

        // Получаем список активных тегов
        $request = new Request();
        $activeTags = $request->tags;
        $unselectableTag = $request->tagPageId;
        if ($activeTags) {
            $activeTags = explode(',', $activeTags);
            foreach ($activeTags as $key => $value) {
                if (trim($value) === '' || trim($value) === '0') {
                    unset($activeTags[$key]);
                }
            }
        } else {
            $activeTags = [];
        }

        // Представляем теги в виде иерархического массива
        $tags = ['top' => []];
        foreach ($result as $tag) {
            $isActive = 0;
            $unselectable = 0;
            if ((isset($lastPath['url']) && $tag['url'] === $lastPath['url'])
                || in_array($tag['ID'], $activeTags, false)) {
                $isActive = 1;
            }

            if ($unselectableTag && $tag['ID'] == $unselectableTag) {
                $unselectable = 1;
            }

            // Получаем cid тега в зависимости от его уровня
            if ($tag['lvl'] == 1) {
                $cid = substr($tag['cid'], 0, $tag['lvl'] * 3);
                $tags['top'][] = [
                    'name' => $tag['name'],
                    'url' => $tag['url'],
                    'cid' => $cid,
                    'isActive' => $isActive,
                    'unselectable' => $unselectable,
                    'ID' => $tag['ID'],
                    'lvl' => 1,
                ];
            } else {
                if ($this->allowedIds && !in_array($tag['ID'], $this->allowedIds)) {
                    continue;
                }

                $parentCid = substr($tag['cid'], 0, ($tag['lvl'] - 1) * 3);
                $cid = substr($tag['cid'], 0, $tag['lvl'] * 3);
                $tags[$parentCid][] = [
                    'name' => $tag['name'],
                    'url' => $tag['url'],
                    'cid' => $cid,
                    'isActive' => $isActive,
                    'unselectable' => $unselectable,
                    'ID' => $tag['ID'],
                    'lvl' => $tag['lvl'],
                ];

                if ($isActive !== 0) {
                    // Обозначаем активный родительский тег
                    $this->setActiveTag($tags, $parentCid, $tag['lvl']);
                }
            }
        }

        return $tags;
    }

    /**
     * Устанавливаем массив идентификаторов тегов доступных для выборки
     */
    public function setAllowedIds(array $allowedIds): void
    {
        $this->allowedIds = $allowedIds;
    }

    private function setActiveTag(array &$tags, $parentCid, $lvl): void
    {
        if ($lvl == 2) {
            $key = 'top';
        } else {
            // Ищем ключ массива с возможными родителями
            $key = substr($parentCid, 0, ($lvl - 2) * 3);
        }

        foreach ($tags[$key] as $k => $v) {
            if ($v['cid'] == $parentCid) {
                $tags[$key][$k]['isActive'] = 1;
                if ($v['lvl'] > 1) {
                    $parentCid = substr($v['cid'], 0, ($v['lvl'] - 1) * 3);
                    self::setActiveTag($tags, $parentCid, $v['lvl']);
                }

                break;
            }
        }
    }
}
