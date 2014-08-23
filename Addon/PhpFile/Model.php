<?php
namespace Ideal\Addon\PhpFile;

class Model extends \Ideal\Core\Admin\Model
{
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        $mode = explode('\\', get_class($this->parentModel));
        if ($mode[3] == 'Site') {
            // Для фронтенда к контенту добавляется выполнение указанного файла
            if ($this->pageData['php_file'] != '') {
                require DOCUMENT_ROOT . $this->pageData['php_file'];
            }
        }
        return $this->pageData;
    }
}
