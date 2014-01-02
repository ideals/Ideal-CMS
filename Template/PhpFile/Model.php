<?php
namespace Ideal\Template\PhpFile;

class Model extends \Ideal\Core\Admin\Model
{

    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);
        if ($this->pageData['php_file'] != '') {
            require DOCUMENT_ROOT . $this->pageData['php_file'];
        }
        return $this->pageData;
    }

}