<?php
namespace Ideal\Template\Page;

class Model extends \Ideal\Core\Admin\Model
{

    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);
        return $this->pageData;
    }
}
