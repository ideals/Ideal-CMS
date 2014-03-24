<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Medium\TemplateList;

use Ideal\Medium\AbstractModel;
use Ideal\Core\Util;

class Model extends AbstractModel
{
    public function getList()
    {
        // Регистрируем объект пользователя
        // require_once 'structures/user/userModel.php';
        // $user = userModel::getInstance();

        // Получаем список шаблонов, которые можно создавать в этой структуре
        $templates = $this->obj->fields[$this->filedName]['templates'];
        foreach ($templates as $template) {
            $class = Util::getClassName($template, 'Template');
            $folder = ltrim(ltrim(str_replace('\\', '/', $class), '/'), 'Ideal/');
            $arr = require($folder . '/config.php');
            $list[$template] = $arr['params']['name'];
        }

        return $list;
    }

}
