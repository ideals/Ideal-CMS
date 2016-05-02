<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2016 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Acl\Admin;

use \Ideal\Core\Db;
use \Ideal\Core\Config;
use \Ideal\Structure\User\Model as User;

/**
 * Класс для работы с правами доступа пользователей
 *
 */
class Model
{
    /**
     * Список пользователей, которым можно менять права в админке
     *
     * @return array Пользователи
     */
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

    /**
     * Удаление из массива элементов, просмотр которых запрещён правами доступа
     *
     * @param string $structureName Название структуры, откуда берутся элементч
     * @param array $arr Список элементов структуры для фильтрации
     * @param int $userId Идентификатор пользователя, для которого проверяются права
     * @return array Список без запрещённых элементов
     */
    public function filterShow($structureName, $arr, $userId)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        // Определяем префикс структуры
        $prefix = '0-';
        if (!empty($structureName)) {
            $str = $config->getStructureByName($structureName);
            $prefix = $str['ID'] . '-';
        }
        // Считываем права пользователя
        $table = $config->db['prefix'] . 'ideal_service_acl';
        $sql = "SELECT * FROM {$table} WHERE user_id={$userId}";
        $result = $db->select($sql);
        $res = array();
        foreach ($result as $v) {
            $res[$v['structure']] = $v;
        }
        // Проводим проверку прав пользователя на каждый элемент
        $result = array();
        foreach ($arr as $v) {
            $structure = $prefix . $v['ID'];
            if (!empty($res[$structure]) && !$res[$structure]['show']) {
                continue;
            }
            $result[] = $v;
        }
        return $result;
    }
}
