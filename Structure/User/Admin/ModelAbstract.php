<?php
namespace Ideal\Structure\User\Admin;

use Ideal\Core\Db;

class ModelAbstract extends \Ideal\Core\Admin\Model
{

    public function delete()
    {
        $db = Db::getInstance();
        $db->delete($this->_table, $this->pageData['ID']);
        // TODO сделать проверку успешности удаления
        return 1;
    }

    public function detectPageByIds($path, $par)
    {
        $this->path = $path;
        return $this;
    }

    public function setPageDataNew()
    {
        parent::setPageDataNew();
        $this->pageData['last_visit'] = '0';
    }
}