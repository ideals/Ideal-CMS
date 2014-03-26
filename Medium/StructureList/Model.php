<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Medium\StructureList;

use Ideal\Medium\AbstractModel;
use Ideal\Core\Config;

class Model extends AbstractModel
{
    public function getList()
    {
        // Получаем список структур, которые можно создавать в этой структуре
        $modelStructures = $this->obj->params['structures'];
        $config = Config::getInstance();
        foreach ($config->structures as $structure) {
            if (in_array($structure['structure'], $modelStructures)) {
                // Удаляем из списка структур на создание те, что нельзя создавать этому юзеру
                // TODO сделать проверку есть ли доступ у юзера к этой структуре
                // $user->enableStructure($structure['structure']);
                $list[$structure['structure']] = $structure['name'];
            }
        }

        return $list;
    }

}
