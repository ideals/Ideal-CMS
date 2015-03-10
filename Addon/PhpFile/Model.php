<?php
namespace Ideal\Addon\PhpFile;

class Model extends \Ideal\Addon\AbstractModel
{
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        $mode = explode('\\', get_class($this->parentModel));
        if ($mode[3] == 'Site') {
            // Для фронтенда к контенту добавляется выполнение указанного файла
            if ($this->pageData['php_file'] != '') {
                if (file_exists(DOCUMENT_ROOT . $this->pageData['php_file'])) {
                    require DOCUMENT_ROOT . $this->pageData['php_file'];
                } else {
                    $this->pageData['content'] = 'Не удалось подключить файл "' . DOCUMENT_ROOT . $this->pageData['php_file'] . '"<br />' . $this->pageData['content'];
                }
            }
        }
        return $this->pageData;
    }
}
