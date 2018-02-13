<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Roster\Admin;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Util;

/**
 * Класс для работы со списками
 *
 */
class ModelAbstract extends \Ideal\Core\Admin\Model
{

    public function delete()
    {
        parent::delete();
        $db = Db::getInstance();
        $db->delete($this->_table)
            ->where('ID=:id', array('id' => $this->pageData['ID']))
            ->exec();

        if (isset($this->pageData['pos'])) {
            // Если есть сортировка pos, то нужно уменьшить все следующие за удаляемым
            // элементы на единицу
            $_sql = "UPDATE {$this->_table} SET pos = pos - 1
                        WHERE prev_structure = '{$this->prevStructure}'
                              AND pos > {$this->pageData['pos']}";
            $db->query($_sql);
        }
        // TODO сделать проверку успешности удаления
        return 1;
    }

    public function detectPageByIds($path, $par)
    {
        $this->path = $path;
        $first = array_shift($par);
        if ($first == null) {
            return $this;
        }

        $first = intval($first);
        $_sql = "SELECT * FROM {$this->_table} WHERE ID={$first}";
        $db = Db::getInstance();
        $localPath = $db->select($_sql);
        if (!isset($localPath[0]['ID'])) {
            $this->is404 = true;
            return $this;
        }
        array_push($this->path, $localPath[0]);

        if (0 != count($par)) {
            // Ещё остались неопределённые элементы пути. Запускаем вложенную структуру.
            $config = Config::getInstance();
            $trueResult = $this->path;
            $end = array_pop($trueResult);
            $prev = array_pop($trueResult);
            $structure = $config->getStructureByName($prev['structure']);
            $modelClassName = Util::getClassName($end['structure'], 'Structure') . '\\Admin\\Model';
            /* @var $structure Model */
            $structure = new $modelClassName($structure['ID'] . '-' . $end['ID']);
            // Запускаем определение пути и активного объекта по $par
            $model = $structure->detectPageByIds($this->path, $par);
            return $model;
        }
        return $this;
    }
}
