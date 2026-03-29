<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\User\Admin;

use Ideal\Core\Admin\Model;
use Ideal\Core\Db;

class ModelAbstract extends Model
{
    public function delete(): void
    {
        parent::delete();
        $db = Db::getInstance();
        $db->delete($this->_table)->where('ID=:id', ['id' => $this->pageData['ID']])->exec();
        // TODO сделать проверку успешности удаления
    }

    public function detectPageByIds($path, $par): self
    {
        $this->path = $path;
        return $this;
    }
}
