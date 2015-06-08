<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Medium\TemplateList;

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
        // Получаем список шаблонов, которые можно создавать в этой структуре
        $templates = $this->obj->fields[$this->fieldName]['templates'];
        $list = array();
        foreach ($templates as $template) {
            $class = Util::getClassName($template, 'Template');
            $folder = ltrim(ltrim(str_replace('\\', '/', $class), '/'), 'Ideal/');
            $arr = require($folder . '/config.php');
            $list[$template] = $arr['params']['name'];
        }
        return $list;
    }
}
