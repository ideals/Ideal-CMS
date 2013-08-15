<?php
namespace Ideal\Structure\Part\Getters;

use Ideal\Core\Util;

class templateList
{
    function getList($obj, $fieldName)
    {
        // Регистрируем объект пользователя
        // require_once 'structures/user/userModel.php';
        // $user = userModel::getInstance();

        // Получаем список шаблонов, которые можно создавать в этой структуре
        $templates = $obj->fields[$fieldName]['templates'];
        foreach ($templates as $template) {
            $class = Util::getClassName($template, 'Template');
            $folder = ltrim(ltrim(str_replace('\\', '/', $class), '/'), 'Ideal/');
            $arr = require($folder  . '/config.php');
            $list[$template] = $arr['params']['name'];
        }

        return $list;
    }
}