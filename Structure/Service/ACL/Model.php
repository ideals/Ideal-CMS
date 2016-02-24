<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2016 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\ACL;

use \Ideal\Core\Db;
use \Ideal\Core\Config;
use \Ideal\Structure\User\Model as User;

/**
 * Класс для работы с правами доступа пользователей
 *
 */
class Model
{
    public static function getAllUsers()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $user = new User();
        $excludedIds = array(1, $user->data['ID']);
        
        // Формируем идентификаторы пользователй правами которых управлять нельзя.
        // Поумолчанию это пользователь с идентификаотром 1 и текущий пользователь
        $par = array('IDS' => implode(',', $excludedIds));
        $userTable = $config->db['prefix'] . 'ideal_structure_user';
        return $db->select("SELECT * FROM {$userTable} WHERE ID NOT IN (:IDS)", $par);
    }
}
