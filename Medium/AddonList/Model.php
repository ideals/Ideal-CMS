<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Medium\AddonList;

use Ideal\Core\Util;
use Ideal\Medium\AbstractModel;

/**
 * Медиум для получения списка шаблонов, которые можно создавать для структуры $obj
 */
class Model extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        $addons = $this->obj->fields[$this->fieldName]['available'];
        $list = array();
        foreach ($addons as $addon) {
            $class = Util::getClassName($addon, 'Addon');
            $folder = ltrim(ltrim(str_replace('\\', '/', $class), '/'), 'Ideal/');
            $arr = require($folder . '/config.php');
            $list[$addon] = $arr['params']['name'];
        }
        return $list;
    }
}
