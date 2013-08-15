<?php
namespace Ideal\Structure\Part\Getters;

use Ideal\Core\Config;

class StructureList
{
    function getList($obj, $fieldName)
    {
        // Получаем список структур, которые можно создавать в этой структуре
        $modelStructures = $obj->params['structures'];
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