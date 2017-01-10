<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Medium\StructureList;

use Ideal\Core\Config;
use Ideal\Medium\AbstractModel;

/**
 * Медиум для получения списка структур, которые можно создавать в структуре $obj
 */
class Model extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        // Получаем список структур, которые можно создавать в этой структуре
        $modelStructures = $this->obj->params['structures'];
        $config = Config::getInstance();
        $list = array();
        foreach ($config->structures as $structure) {
            if (in_array($structure['structure'], $modelStructures)) {
                // Удаляем из списка структур на создание те, что нельзя создавать этому пользователю
                // TODO сделать проверку есть ли доступ у пользователя к этой структуре
                // $user->enableStructure($structure['structure']);
                $list[$structure['structure']] = $structure['name'];
            }
        }
        return $list;
    }
}
