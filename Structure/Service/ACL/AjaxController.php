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

    /**
     * Действие срабатывающее при клике на названии элемента структуры
     */
    public function showChildrenAction()
    {
        $permission = array();
        $db = Db::getInstance();
        $config = Config::getInstance();
        list($structureID, $elementID) = explode('-', $_POST['structure']);
        $structure = $config->getStructureById($structureID);
        $childrenStructures = array();

        // Если элементы структуры не могут быть созданы внутри себя, то всё рвно заносим текущую структуры в список
        if (array_search($structure['structure'], $structure['params']['structures']) === false) {
            $structure['params']['structures'][] = $structure['structure'];
        }

        // Собрать названия таблиц структур, коьторые могут быть созданы в этой структуре
        foreach ($structure['params']['structures'] as $childrenStructure) {
            $childrenStructures[$childrenStructure] = $config->getStructureByName($childrenStructure);
            if (strpos($childrenStructure, '_') !== false) {
                $childrenStructures[$childrenStructure]['tableName'] = $config->getTableByName($childrenStructure);
            }
        }

        // собираем дочерние элементы
        foreach ($childrenStructures as $childrenStructure) {
            // TODO у сервисов нет таблиц, нужно учесть
            if (isset($childrenStructure['tableName'])) {
                $par = array();
                $whereString = '';
                if (isset($childrenStructure['fields']['prev_structure'])) {
                    if ($elementID == 0 || $childrenStructure['structure'] == $structure['structure']) {
                        $par['prev_structure'] =  '0-' . $structureID;
                    } else {
                        $par['prev_structure'] = $structureID . '-' . $elementID;
                    }
                    $whereString .= ' prev_structure = :prev_structure';
                }

                // Если идентификатор элемента равен 0, то собираем только первый уровень.
                if (isset($childrenStructure['fields']['lvl']) && $elementID == 0) {
                    if (!empty($whereString)) {
                        $whereString .= ' AND';
                    }
                    $par['lvl'] = 1;
                    $whereString .= " lvl = :lvl";
                }

                // Уровень ниже
                // TODO учесть медиумы
                if (isset($childrenStructure['fields']['cid']) && $elementID != 0) {
                    if (!empty($whereString)) {
                        $whereString .= ' AND';
                    }
                    $cid = $db->select(
                        "SELECT cid FROM {$childrenStructure['tableName']} WHERE ID = :ID",
                        array('ID' => $elementID)
                    );
                    $cid = str_split($cid[0]['cid'], $childrenStructure['params']['digits']);
                    $cid = array_filter($cid, function ($v) {
                        return intval($v);
                    });
                    $cid = implode('', $cid);
                    $par['ID'] = $elementID;
                    $whereString .= " cid LIKE '{$cid}%' AND ID != :ID";
                }

                // Запрашиваем элементы из базы
                if (!empty($whereString)) {
                    $whereString = ' WHERE' . $whereString;
                }
                $structurePermissions = $db->select(
                    "SELECT * FROM {$childrenStructure['tableName']}{$whereString}",
                    $par
                );

                // Заполняем полученные данные начальными параметрами доступа
                foreach ($structurePermissions as $structurePermission) {
                    // TODO Учесть что может быть выбран тип раздела отличный от структуры дочернего элемента
                    $name = '';
                    if (isset($structurePermission['name'])) {
                        $name = $structurePermission['name'];
                    } elseif (isset($structurePermission['email'])) {
                        $name = $structurePermission['email'];
                    }
                    $permission[$childrenStructure['ID'] . '-' . $structurePermission['ID']] = array(
                        'name' => $name,
                        'show' => 1,
                        'edit' => 1,
                        'delete' => 1,
                        'enter' => 1,
                        'edit_children' => 1,
                        'delete_children' => 1,
                        'enter_children' => 1,
                    );
                    $aclTable = $config->db['prefix'] . 'ideal_service_acl';
                    $userStructurePermissions = $db->select(
                        "SELECT * FROM {$aclTable} WHERE user_id = :user_id AND structure = :structure",
                        array(
                            'user_id' => $_POST['user_id'],
                            'structure' => $childrenStructure['ID'] . '-' . $structurePermission['ID']
                        )
                    );
                    if (!empty($userStructurePermissions)) {
                        foreach ($userStructurePermissions as $userStructurePermission) {
                            if (array_key_exists($userStructurePermission['structure'], $permission)) {
                                $permission[$userStructurePermission['structure']] = array_merge(
                                    $permission[$userStructurePermission['structure']],
                                    $userStructurePermission
                                );
                            }
                        }
                    }
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
