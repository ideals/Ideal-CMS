<?php
namespace Ideal\Addon\Page;

class Model extends \Ideal\Core\Admin\Model
{

    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);
        return $this->pageData;
    }
}
