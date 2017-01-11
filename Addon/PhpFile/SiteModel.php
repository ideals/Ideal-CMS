<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon\PhpFile;

use Ideal\Addon\AbstractSiteModel;

class SiteModel extends AbstractSiteModel
{
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        // Для фронтенда к контенту добавляется выполнение указанного файла
        if ($this->pageData['php_file'] != '') {
            if (file_exists(DOCUMENT_ROOT . $this->pageData['php_file'])) {
                require DOCUMENT_ROOT . $this->pageData['php_file'];
            } else {
                $this->pageData['content'] = 'Не удалось подключить файл "' . DOCUMENT_ROOT
                    . $this->pageData['php_file'] . '"<br />' . $this->pageData['content'];
            }
        }

        return $this->pageData;
    }
}
