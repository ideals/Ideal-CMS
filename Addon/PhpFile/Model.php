<?php
namespace Ideal\Addon\PhpFile;

use Ideal\Core\Util;

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
                    Util::addError('Не удалось подключить файл "' . DOCUMENT_ROOT . $this->pageData['php_file'] . '"');
                    $this->pageData['content'] =  $this->pageData['content'];
                }
            }
        }
        return $this->pageData;
    }
}
