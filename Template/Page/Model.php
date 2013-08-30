<?php
namespace Ideal\Template\Page;

class Model extends \Ideal\Core\Admin\Model
{

    public function getObject($model)
    {
        $this->setObjectByprevStructure($this->prevStructure);
        return $this->object;
    }


    public function setObjectNew()
    {

    }

}