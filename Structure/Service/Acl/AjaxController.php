<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2016 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Structure\Service\Acl;

use \Ideal\Core\Db;
use \Ideal\Core\Config;
use Ideal\Structure\Service\Admin\Model as ServiceModel;

/**
 * Реакция на действия со страницы "Права доступа"
 *
 */
class AjaxController extends \Ideal\Core\AjaxController
{

    /**
     * Формирование списка первого уровня для управления правами
     */
    public function mainUserPermissionAction()
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

        // Получаем все права пользователя на основные пункты меню админки
        $par = array('user_id' => $_POST['user_id']);
        $whereString = ' WHERE user_id = :user_id AND structure LIKE \'0-%\'';
        $userPermissions = $this->getExistingAccessRules($par, $whereString);

        // Заменяем правила по умолчанию на уже известные правила для каждого пункта
        $this->applyKnownRules($userPermissions, $permission);

        return json_encode($permission, JSON_FORCE_OBJECT);
    }

    /**
     * Формирование списка дочерних пунктов для управления правами
     */
    public function showChildrenAction()
    {
        $permission = array();
        $db = Db::getInstance();
        $config = Config::getInstance();

        // Получаем идентификатор структуры и идентификатор элемента структуры родительского пункта
        list($structureID, $elementID) = explode('-', $_POST['structure']);

        // Получаем информацию о структуре к которой относится родительский пункт
        $structure = $config->getStructureById($structureID);
        $childrenStructure = array();

        // Если запрашиваются дочерние элементы пункта отличного от "Сервис"
        if ($structure['structure'] != 'Ideal_Service') {
            // Если идентификатор элемента == 0, то берём из таблицы структуры, чей идентификатор был так же передан
            if ($structureID == 0) {
                // Получаем информацию о структуре, к которой относятся дочерние элементы данного пункта
                $childrenStructure = $config->getStructureById($elementID);
                $childrenStructure['tableName'] = $config->getTableByName($childrenStructure['structure']);
            } else {
                // Если идентификатор элемента отличен от нуля, то сначала узнаём тип раздела
                $structureTable = $config->getTableByName($structure['structure']);
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

            // Собираем дочерние элементы
            if (!empty($childrenStructure)) {
                // Параметры для поиска дочерних элементов
                $par = array();

                // Строка, которая будет использоваться в WHERE-части запроса
                $whereString = '';

                // Если у дочерней структуры есть поле 'prev_structure',
                // то добавляем соответствующие записи в WHERE-часть запроса
                if (isset($childrenStructure['fields']['prev_structure'])) {
                    // Если тип дочерней структуры равен типу родительской структуры,
                    // то используем явно переданное значение 'prev_structure'
                    if ($childrenStructure['structure'] == $structure['structure']) {
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
                if (isset($childrenStructure['fields']['lvl']) && (
                        $elementID == 0 || $childrenStructure['structure'] != $structure['structure']
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
                if (isset($childrenStructure['fields']['cid']) && $elementID != 0
                    && $childrenStructure['structure'] == $structure['structure']) {

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
            }
        } elseif (strpos($elementID, '_') === false) {
            // Если запрашиваются дочерние элементы пункта "Сервис", то собираем их по особенному
            // Второй уровень вложенности пункта "Сервис" (вкладки), не обслуживается
            $service = new ServiceModel('');
            $structurePermissions = $service->getMenu();
            $childrenStructure['ID'] = $structureID;
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
                        'user_id' => $_POST['user_id'],
                        'structure' =>  $childrenStructure['ID'] . '-' . $structurePermission['ID']
                    );
                    $whereString = ' WHERE user_id = :user_id AND structure = :structure';
                    $userStructurePermissions = $this->getExistingAccessRules($par, $whereString);

                    $this->applyKnownRules($userStructurePermissions, $permission);
                }
            }
        }
        return json_encode($permission, JSON_FORCE_OBJECT);
    }

    /**
     * Занесение в базу изменённого правила для соответствующего пункта
     */
    public function changePermissionAction()
    {
        $permission = $this->getDefaultPermissionArray();
        $permission['user_id'] = $_POST['user_id'];
        $permission['structure'] = $_POST['structure'];

        // Добавляем именно то, правило, которое следует заменить у выбранного пункта
        $permission[$_POST['target']] = $_POST['is'];

        $db = Db::getInstance();
        $config = Config::getInstance();
        $aclTable = $config->db['prefix'] . 'ideal_service_acl';

        $par = array('user_id' => $_POST['user_id'], 'structure' => $_POST['structure']);
        $whereString = ' WHERE user_id = :user_id AND structure = :structure';
        $userPermission = $this->getExistingAccessRules($par, $whereString);

        // Если записи ещё нет, то заводим её
        if (empty($userPermission)) {
            $db->insert($aclTable, $permission);
        } else {
            // Если запись есть, обновляем
            $values = array($_POST['target'] => $_POST['is']);
            $params = array('user_id' => $_POST['user_id'], 'structure' => $_POST['structure']);
            $db->update($aclTable)->set($values);
            $db->where('user_id = :user_id AND structure = :structure', $params)->exec();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpHeaders()
    {
        return array(
            'Content-type' => 'Content-type: application/json'
        );
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
        $config = Config::getInstance();
        $aclTable = $config->db['prefix'] . 'ideal_service_acl';
        return $db->select(
            "SELECT * FROM {$aclTable}{$whereString}",
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
