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
    /** @var string Название таблицы со списком прав доступа */
    protected $table;

    /** @var User Объект авторизованного пользователя*/
    protected $user;

    /**
     * Model constructor.
     */
    public function __construct()
    {
        $config = Config::getInstance();
        $this->table = $config->db['prefix'] . 'ideal_service_acl';
        $this->user = User::getInstance();
    }

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
        
        // Формируем идентификаторы пользователей, правами которых управлять нельзя.
        // По умолчанию это пользователь с идентификатором 1 и текущий пользователь
        $par = array('IDS' => implode(',', $excludedIds));
        $userTable = $config->db['prefix'] . 'ideal_structure_user';
        return $db->select("SELECT * FROM {$userTable} WHERE ID NOT IN (:IDS)", $par);
    }

    /**
     * Удаление из массива элементов, просмотр которых запрещён правами доступа
     *
     * @param string $structureName Название структуры, откуда берутся элементы
     * @param array $arr Список элементов структуры для фильтрации
     * @return array Список без запрещённых элементов
     */
    public function filterShow($structureName, $arr)
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
        $sql = "SELECT * FROM {$this->table} WHERE user_id={$this->user->data['ID']}";
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

    /**
     * Получение прав доступа для текущего пользователя на список структур
     *
     * @param array $structures Список структур, для которых нужно получить права доступа
     * @return array Права доступа для структур
     */
    public function getAcl($structures)
    {
        $db = Db::getInstance();
        $sql = "SELECT * FROM {$this->table}"
            . " WHERE structure IN ('" . implode("','", $structures) . "') AND user_id={$this->user->data['ID']}";
        $acl = $db->select($sql);
        // Распределяем считанные права доступа по структурам
        $aclStructure = array();
        foreach ($acl as $v) {
            $aclStructure[$v['structure']] = $v;
        }
        return $aclStructure;
    }

    /**
     * Проверка, имеет ли пользователь доступ к этой структуре
     *
     * @param \Ideal\Core\Admin\Model $model
     * @return bool
     */
    public function checkAccess($model)
    {
        $data = $model->getPageData();
        $config = Config::getInstance();
        $structure = $config->getStructureByClass(get_class($model));
        $structure = $structure['ID'] . '-' . $data['ID'];

        // Получаем права на структуру из БД
        $db = Db::getInstance();
        $sql = "SELECT * FROM {$this->table}"
            . " WHERE structure='{$structure}' AND user_id={$this->user->data['ID']}";
        $acl = $db->select($sql);

        $access = true;
        if (isset($acl[0])) {
            // Если права для этого раздела прописаны, то должен быть разрешён и показ и вход в него
            $access = $acl[0]['show'] && $acl[0]['enter'];
        }

        return $access;
    }
}
