<?php
namespace Ideal\Structure\User\Admin;

use Ideal\Core\Db;

class ModelAbstract extends \Ideal\Core\Admin\Model
{

    public function detectPageByIds($path, $par, $prevStructureId)
    {
        $this->initPageData();
        $this->setPageDataById($prevStructureId);
        $this->path = $path;
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