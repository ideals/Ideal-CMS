<?php
namespace Ideal\Template\PhpFile;

class Model extends \Ideal\Core\Admin\Model
{

    public function getObject($parentModel)
    {
        $this->setObjectByStructurePath($this->structurePath);
        if ($this->object['php_file'] != '') {
            require DOCUMENT_ROOT . $this->object['php_file'];
        }
        return $this->object;
    }


    public function setObjectNew()
    {

    }

}