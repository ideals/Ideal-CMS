<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2016 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Structure\Service\ACL;

use \Ideal\Core\Db;
use \Ideal\Core\Config;

/**
 * Реакция на действия со страницы "Права доступа"
 *
 */
class AjaxController extends \Ideal\Core\AjaxController
{

    /**
     * Действие срабатывающее при выборе пользователя
     */
    public function mainUserPermissionAction()
    {
        $permission = array();
        $db = Db::getInstance();
        $config = Config::getInstance();

        // Собираем начальную информацию об основных пунктах меню админки
        foreach ($config->structures as $structure) {
            if ($structure['isShow']) {
                $permission[$structure['ID'] . '-0'] = array(
                    'name' => $structure['name'],
                    'show' => 1,
                    'edit' => 1,
                    'delete' => 1,
                    'enter' => 1,
                    'edit_children' => 1,
                    'delete_children' => 1,
                    'enter_children' => 1,
                );
            }
        }

        // Получаем все права пользователя на основные пункты меню админки
        $par = array('user_id' => $_POST['user_id']);
        $aclTable = $config->db['prefix'] . 'ideal_service_acl';
        $userPermissions = $db->select(
            "SELECT * FROM {$aclTable} WHERE user_id = :user_id AND structure LIKE '%-0'",
            $par
        );
        if (!empty($userPermissions)) {
            foreach ($userPermissions as $userPermission) {
                if (array_key_exists($userPermission['structure'], $permission)) {
                    $permission[$userPermission['structure']] = array_merge(
                        $permission[$userPermission['structure']],
                        $userPermission
                    );
                }
            }
        }
        return json_encode($permission);
    }

    public function changePermissionAction()
    {
        $permission = array(
            'user_id' => $_POST['user_id'],
            'structure' => $_POST['structure'],
            'show' => 1,
            'edit' => 1,
            'delete' => 1,
            'enter' => 1,
            'edit_children' => 1,
            'delete_children' => 1,
            'enter_children' => 1,
        );
        $permission[$_POST['target']] = $_POST['is'];
        $db = Db::getInstance();
        $config = Config::getInstance();
        $par = array('user_id' => $_POST['user_id'], 'structure' => $_POST['structure']);
        $aclTable = $config->db['prefix'] . 'ideal_service_acl';
        $userPermission = $db->select(
            "SELECT * FROM {$aclTable} WHERE user_id = :user_id AND structure = :structure",
            $par
        );

        // Если записи ещё нет, то заводим её
        if (empty($userPermission)) {
            $db->insert($aclTable, $permission);
        } else {
            // Если запись нет, обновляем.
            $values = array($_POST['target'] => $_POST['is']);
            $params = array('user_id' => $_POST['user_id'], 'structure' => $_POST['structure']);
            $db->update($aclTable)->set($values);
            $db->where('user_id = :user_id AND structure = :structure', $params)->exec();
        }
    }

    public function getHttpHeaders()
    {
        return array(
            'Content-type' => 'Content-type: application/json'
        );
    }
}
