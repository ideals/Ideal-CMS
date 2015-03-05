<?php
namespace Ideal\Addon\Page;

class Model extends \Ideal\Addon\AbstractModel
{

    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);
        return $this->pageData;
    }
}
