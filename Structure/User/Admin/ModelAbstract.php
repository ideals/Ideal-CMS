<?php
namespace Ideal\Structure\User\Admin;

use Ideal\Core\Db;

class ModelAbstract extends \Ideal\Core\Admin\Model
{

    public function detectPageByIds($par)
    {
        $this->path = array();
        return array();
    }


    /**
     * @param int $page Номер отображаемой страницы
     * @param int $onPage Кол-во элементов на странице
     * @return array Полученный список элементов
     */
    public function getList($page, $onPage)
    {
        $_sql = "SELECT * FROM {$this->_table} ORDER BY {$this->params['field_sort']} LIMIT {$page}, {$onPage}";
        $db = Db::getInstance();
        $list = $db->queryArray($_sql);

        return $list;
    }


    public function setObjectNew()
    {
        $this->object['last_visit'] = '0';
    }


    public function delete()
    {
        $db = Db::getInstance();
        $db->delete($this->_table, $this->object['ID']);
        // TODO сделать проверку успешности удаления
        return 1;
    }
}