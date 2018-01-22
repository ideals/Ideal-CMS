<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Acl\Admin;

use \Ideal\Core\Db;
use \Ideal\Core\Config;
use \Ideal\Structure\User\Model as User;
use Ideal\Structure\Service\Admin\Model as ServiceModel;

/**
 * Класс для работы с правами доступа пользователей
 *
 */
class Model
{
    /** @var string Название таблицы со списком прав доступа */
    protected $table;

    /** @var User Объект авторизованного пользователя */
    protected $user;

    /**
     * Model constructor.
     */
    public function __construct()
    {
        $config = Config::getInstance();
        $this->table = $config->db['prefix'] . 'ideal_structure_acl';
        $this->user = User::getInstance();
    }

    /**
     * Список групп пользователей, которым можно менять права в админке
     *
     * @return array Пользователи
     */
    public static function getAllUserGroups()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $user = new User();
        $excludedIds = array(1, $user->data['ID']);

        // Формируем идентификаторы групп пользователей, правами которых управлять нельзя.
        // По умолчанию это группы пользователей
        // к которым относятся пользователи с идентификатором 1 и текущий пользователь
        $par = array('IDS' => implode(',', $excludedIds));
        $userTable = $config->db['prefix'] . 'ideal_structure_user';
        $userGroupTable = $config->db['prefix'] . 'ideal_structure_usergroup';
        $sql = "
          SELECT 
            {$userGroupTable}.ID, 
            {$userGroupTable}.name
          FROM 
            {$userGroupTable} 
          LEFT JOIN {$userTable} 
          ON {$userGroupTable}.ID = {$userTable}.user_group
          WHERE 
            {$userTable}.ID NOT IN (:IDS)
            OR {$userTable}.ID IS NULL
          GROUP BY {$userGroupTable}.ID";
        return $db->select($sql, $par);
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
        $res = array();
        if (isset($this->user->data) && isset($this->user->data['user_group']) && $this->user->data['user_group']) {
            $sql = "SELECT * FROM {$this->table} WHERE user_group_id={$this->user->data['user_group']}";
            $result = $db->select($sql);
            foreach ($result as $v) {
                $res[$v['structure']] = $v;
            }
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
        $aclStructure = array();
        if ($this->user->data['user_group']) {
            $db = Db::getInstance();
            $sql = "SELECT * FROM {$this->table} "
                . "WHERE structure IN ('" . implode("','", $structures) . "') "
                . "AND user_group_id={$this->user->data['user_group']}";
            $acl = $db->select($sql);
            // Распределяем считанные права доступа по структурам
            $aclStructure = array();
            foreach ($acl as $v) {
                $aclStructure[$v['structure']] = $v;
            }
        }
        return $aclStructure;
    }

    /**
     * Проверка, имеет ли пользователь доступ к этой структуре
     *
     * @param \Ideal\Core\Admin\Model $model
     * @param string $action
     * @return bool
     */
    public function checkAccess($model, $action = 'access')
    {
        $access = true;
        if (isset($this->user->data) && isset($this->user->data['user_group']) && $this->user->data['user_group']) {
            $data = $model->getPageData();
            if (empty($data['prev_structure'])) {
                $structure = '0-' . $data['ID'];
            } else {
                $config = Config::getInstance();
                $structure = $config->getStructureByClass(get_class($model));
                $structure = $structure['ID'] . '-' . $data['ID'];
            }

            // Получаем права на структуру из БД
            $db = Db::getInstance();
            $sql = "SELECT * FROM {$this->table}"
                . " WHERE structure='{$structure}' AND user_group_id={$this->user->data['user_group']}";
            $acl = $db->select($sql);

            if (isset($acl[0])) {
                if ($action == 'access') {
                    // Если права для этого раздела прописаны, то должен быть разрешён и показ и вход в него
                    $access = $acl[0]['show'] && $acl[0]['enter'];
                } else {
                    $access = $acl[0][$action];
                }
            }
        }

        return $access;
    }

    /**
     * Формирование списка первого уровня для управления правами
     */
    public function getMainUserGroupPermission()
    {
        $permission = array();
        $config = Config::getInstance();

        // Собираем начальную информацию об основных пунктах меню админки
        foreach ($config->structures as $structure) {
            if ($structure['isShow']) {
                $permission['0-' . $structure['ID']] = $this->getDefaultPermissionArray();
                $permission['0-' . $structure['ID']]['name'] = $structure['name'];
                $permission['0-' . $structure['ID']]['prev_structure'] = '0-' . $structure['ID'];
            }
        }

        // Получаем все права группы пользователя на основные пункты меню админки
        $par = array('user_group_id' => $_POST['user_group_id']);
        $whereString = ' WHERE user_group_id = :user_group_id AND structure LIKE \'0-%\'';
        $userPermissions = $this->getExistingAccessRules($par, $whereString);

        // Заменяем правила по умолчанию на уже известные правила для каждого пункта
        $this->applyKnownRules($userPermissions, $permission);

        return $permission;
    }

    /**
     * Формирование списка дочерних пунктов для управления правами
     */
    public function getChildrenPermission()
    {
        $permission = array();
        $db = Db::getInstance();
        $config = Config::getInstance();

        // Получаем идентификатор структуры и идентификатор элемента структуры родительского пункта
        list($structureID, $elementID) = explode('-', $_POST['structure']);

        // Получаем информацию о структуре к которой относится родительский пункт
        $structure = $config->getStructureById($structureID);
        $childrenStructure = array();

        // Если идентификатор структуры == 0, то берём элементы из таблицы структуры, чей идентификатор был передан
        if ($structureID == 0) {
            // Получаем информацию о структуре, к которой относятся дочерние элементы данного пункта
            $childrenStructure = $config->getStructureById($elementID);
            $childrenStructure['tableName'] = $config->getTableByName($childrenStructure['structure']);
        } else {
            // Если идентификатор структуры отличен от нуля, то сначала узнаём тип раздела
            $structureTable = $config->getTableByName($structure['structure']);

            // Для дочерних элементов пункта "Сервис" не нужно пытаться получать информацию о структуре
            if (strpos($structureTable, 'ideal_structure_service') === false) {
                $partitionType = $db->select(
                    "SELECT * FROM {$structureTable} WHERE ID = :ID",
                    array(
                        'ID' => $elementID
                    )
                );
                if (!empty($partitionType) && isset($partitionType[0]['structure'])) {
                    // Получаем информацию о структуре, к которой относятся дочерние элементы данного пункта
                    $childrenStructure = $config->getStructureByName($partitionType[0]['structure']);
                    $childrenStructure['tableName'] = $config->getTableByName($partitionType[0]['structure']);
                }
            }
        }

        // Собираем дочерние элементы
        if (!empty($childrenStructure)) {

            // Если запрашиваются дочерние элементы пункта отличного от "Сервис"
            if (isset($childrenStructure['structure']) && $childrenStructure['structure'] != 'Ideal_Service') {

                // Параметры для поиска дочерних элементов
                $par = array();

                // Строка, которая будет использоваться в WHERE-части запроса
                $whereString = '';

                // Если у дочерней структуры есть поле 'prev_structure',
                // то добавляем соответствующие записи в WHERE-часть запроса
                if (isset($childrenStructure['fields']['prev_structure'])) {
                    // Если тип дочерней структуры равен типу родительской структуры,
                    // то используем явно переданное значение 'prev_structure'
                    if (isset($structure['structure']) && $childrenStructure['structure'] == $structure['structure']) {
                        $par['prev_structure'] = $_POST['prev_structure'];
                    } else {
                        // Если тип дочерней структуры отличен от типа родительской структуры,
                        // то генерируем новое значение 'prev_structure'
                        $par['prev_structure'] = $structureID . '-' . $elementID;
                    }
                    $prev_structure = $par['prev_structure'];
                    $whereString .= ' prev_structure = :prev_structure';
                }

                // Если у дочерней структуры есть поле 'lvl', то добавляем соответствующие записи в WHERE-часть запроса
                // Если идентификатор элемента равен 0 или тип родительской структуры отличается
                // от типа дочерней структуры, то собираем только первый уровень.
                if (isset($childrenStructure['fields']['lvl']) &&
                    ($structureID == 0 || !isset($structure['structure']) ||
                        $childrenStructure['structure'] != $structure['structure']
                    )) {
                    if (!empty($whereString)) {
                        $whereString .= ' AND';
                    }
                    $par['lvl'] = 1;
                    $whereString .= " lvl = :lvl";
                }

                // Если у дочерней структуры есть поле 'cid',
                // родительская структура не относится к пунктам верхнего меню админки
                // и тип родительской структуры равен типу дочерней структуры,
                // то добавляем соответствующие записи в WHERE-часть запроса
                if (isset($childrenStructure['fields']['cid']) && $structureID != 0
                    && isset($structure['structure']) && $childrenStructure['structure'] == $structure['structure']) {

                    if (!empty($whereString)) {
                        $whereString .= ' AND';
                    }

                    // Получаем параметры вложенности дочерней структуры
                    $digits = $childrenStructure['params']['digits'];
                    $levels = $childrenStructure['params']['levels'];

                    // Получаем cid родительского элемента
                    $cid = $db->select(
                        "SELECT cid FROM {$childrenStructure['tableName']} WHERE ID = :ID",
                        array('ID' => $elementID)
                    );

                    // Формируем cid для WHERE-части запроса на выборку дочерних элементов
                    $cid = str_split($cid[0]['cid'], $digits);
                    $cid = array_filter($cid, function ($v) {
                        return intval($v);
                    });
                    $cid = implode('', $cid);
                    $cidRegexpString = '^' . $cid . '(.){' . $digits . '}';
                    if (strlen($cidRegexpString) < $digits * $levels) {
                        $cidRegexpString .= str_repeat('0', $digits);
                    }
                    $par['ID'] = $elementID;
                    $whereString .= " cid REGEXP '{$cidRegexpString }' AND ID != :ID";
                }

                // Завершаем формирование WHERE-части запроса
                if (!empty($whereString)) {
                    $whereString = ' WHERE' . $whereString;
                }

                // Получаем дочерние элементы текущего пункта
                $structurePermissions = $db->select(
                    "SELECT * FROM {$childrenStructure['tableName']}{$whereString}",
                    $par
                );
            } elseif (strpos($elementID, '_') === false) {
                // Если запрашиваются дочерние элементы пункта "Сервис", то собираем их по особенному
                // Второй уровень вложенности пункта "Сервис" (вкладки), не обслуживается
                $service = new ServiceModel('');
                $structurePermissions = $service->getMenu();
                $childrenStructure['ID'] = $elementID;
            }
        }

        // Если дочерние элементы текущего пункта есть
        if (isset($structurePermissions) && count($structurePermissions) > 0) {
            foreach ($structurePermissions as $structurePermission) {
                // Каждому дочернему элементу добавляем название для отображения в пользовательском интерфейсе
                $name = '';
                if (isset($structurePermission['name'])) {
                    $name = $structurePermission['name'];
                } elseif (isset($structurePermission['email'])) {
                    $name = $structurePermission['email'];
                }

                // Отображаем элемент для управления правами на него только в том случае, когда он имеет название
                if (!empty($name)) {

                    // Формируем ключ массива, который будет использован на фронтенде в качестве значения для data-seid
                    $key = $childrenStructure['ID'] . '-' . $structurePermission['ID'];

                    $permission[$key] = $this->getDefaultPermissionArray();
                    $permission[$key]['name'] = $name;
                    $permission[$key]['prev_structure'] = isset($prev_structure) ? $prev_structure : '';
                    $par = array(
                        'user_group_id' => $_POST['user_group_id'],
                        'structure' => $childrenStructure['ID'] . '-' . $structurePermission['ID']
                    );
                    $whereString = ' WHERE user_group_id = :user_group_id AND structure = :structure';
                    $userGroupStructurePermissions = $this->getExistingAccessRules($par, $whereString);

                    $this->applyKnownRules($userGroupStructurePermissions, $permission);
                }
            }
        }
        return $permission;
    }

    /**
     * Занесение в базу изменённого правила для соответствующего пункта
     */
    public function changePermission()
    {
        $permission = $this->getDefaultPermissionArray();
        $permission['user_group_id'] = $_POST['user_group_id'];
        $permission['structure'] = $_POST['structure'];

        // Добавляем именно то, правило, которое следует заменить у выбранного пункта
        $permission[$_POST['target']] = $_POST['is'];

        $db = Db::getInstance();

        $par = array('user_group_id' => $_POST['user_group_id'], 'structure' => $_POST['structure']);
        $whereString = ' WHERE user_group_id = :user_group_id AND structure = :structure';
        $userGroupPermission = $this->getExistingAccessRules($par, $whereString);

        // Если записи ещё нет, то заводим её
        if (empty($userGroupPermission)) {
            $db->insert($this->table, $permission);
        } else {
            // Если запись есть, обновляем
            $values = array($_POST['target'] => $_POST['is']);
            $params = array('user_group_id' => $_POST['user_group_id'], 'structure' => $_POST['structure']);
            $db->update($this->table)->set($values);
            $db->where('user_group_id = :user_group_id AND structure = :structure', $params)->exec();
        }
    }

    /**
     * Генерирует массив прав по умолчанию для элемента предполагаемого элемента
     *
     * @return array Массив прав
     */
    private function getDefaultPermissionArray()
    {
        return array(
            'show' => 1,
            'edit' => 1,
            'delete' => 1,
            'enter' => 1,
        );
    }

    /**
     * Получает уже установленные правила для элемента
     *
     * @param string $par Набор параметров для выборки
     * @param string $whereString Строка с WHERE-частью запроса
     * @return array Массив выборки правил
     */
    private function getExistingAccessRules($par, $whereString)
    {
        $db = Db::getInstance();
        return $db->select(
            "SELECT * FROM {$this->table}{$whereString}",
            $par
        );
    }

    /**
     * Замена правил по умолчанию на уже установленные правила для соответствующих пунктов
     *
     * @param array $existingRules Существующие правила для пунктов
     * @param array $defaultRules Правила по умолчанию
     */
    private function applyKnownRules($existingRules, &$defaultRules)
    {
        if (!empty($existingRules)) {
            foreach ($existingRules as $rule) {
                if (array_key_exists($rule['structure'], $defaultRules)) {
                    $defaultRules[$rule['structure']] = array_merge(
                        $defaultRules[$rule['structure']],
                        $rule
                    );
                }
            }
        }
    }
}
