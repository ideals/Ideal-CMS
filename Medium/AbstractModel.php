<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Medium;

use Ideal\Core\Db;

class AbstractModel extends \Ideal\Core\Model
{
    protected $filedName;
    protected $obj;

    /**
     * Удаление всех значений для определенного потомка
     * @param int $idChild индефикатор потомка
     */
    public function deleteChild($idChild)
    {
        $db = Db::getInstance();
        $_sql = "DELETE FROM {$this->_table} WHERE id_child={$idChild}";
        $db->query($_sql);
    }

    /**
     * Удаление всех значений для определенного предка
     * @param int $idParent индефикатор предка
     */
    public function deleteParent($idParent)
    {
        $db = Db::getInstance();
        $_sql = "DELETE FROM {$this->_table} WHERE id_parent={$idParent}";
        $db->query($_sql);
    }

    /**
     * Удаление записи о соотнощении предка с потомком
     * @param int $idParent индефикатор предка
     * @param int $idChild индефикатор потомка
     */
    public function deleteRow($idParent, $idChild)
    {
        $db = Db::getInstance();
        $_sql = "DELETE FROM {$this->_table} WHERE id_parent={$idParent} AND id_child={$idChild}";
        $db->query($_sql);
    }

    /**
     * Получения списка всех предков к которому относятся данный потомок
     * @param int $idChild индефикатор потомка
     * @return array|bool в случае успеха вернет массив индефикаторов в противном случае вернет false
     */
    public function getIdParent($idChild)
    {
        $db = Db::getInstance();
        $_sql = "SELECT id_parent FROM {$this->_table} WHERE id_child={$idChild}";
        $result = $db->queryArray($_sql);
        if (count($result) == 0) $result = false;
        return $result;
    }

    /**
     * Получения списка всех потомков к которому относятся данный предок
     * @param $idParent индефикатор предка
     * @return array|bool в случае успеха вернет массив индефикаторов в противном случае вернет false
     */
    public function getIdChild($idParent)
    {
        $db = Db::getInstance();
        $_sql = "SELECT id_child FROM {$this->_table} WHERE id_parent={$idParent}";
        $result = $db->queryArray($_sql);
        if (count($result) == 0) $result = false;
        return $result;
    }

    /**
     * Функция для
     * @return array|void
     * @throws \Exception
     */
    public function getList()
    {
        throw new \Exception('Вызов не переопределённого метода getList');
    }

    /**
     * Сохранение связи предка с потомком
     * @param int $idParent индефикатор предка
     * @param int $idChild индефикатор потомка
     */
    public function setRow($idParent, $idChild)
    {
        $db = Db::getInstance();
        $this->deleteRow($idParent, $idChild);
        $_sql = "INSERT INTO {$this->_table} (id_parent, id_child) VALUE ({$idParent}, {$idChild})";
        $db->query($_sql);
    }

    /**
     * Установка названия поля
     * @param $filedName
     */
    public function setFieldName($filedName)
    {
        $this->filedName = $filedName;
    }

    /**
     * Установка объекта с которым работаем
     * @param $obj
     */
    public function setObj($obj)
    {
        $this->obj = $obj;
    }

}
