<?php
namespace Ideal\Structure\User\Admin;

use Ideal\Core\Db;

class ModelAbstract extends \Ideal\Core\Admin\Model
{

    public function detectPageByIds($path, $par)
    {
        $this->path = $path;
        $this->object = end($path);
        return $this;
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