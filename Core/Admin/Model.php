<?php
namespace Ideal\Core\Admin;

use Ideal\Core;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Util;

abstract class Model extends Core\Model
{

    protected $fieldsGroup = 'general';

    // Создание нового элемента структуры
    public function createElement($result, $groupName = 'general')
    {
        // Из общего списка введённых данных выделяем те, что помечены general
        foreach ($result['items'] as $v) {
            list($group, $field) = explode('_', $v['fieldName'], 2);

            if ($group == $groupName && $field == 'prev_structure' && $v['value'] == '') {
                $result['items'][$v['fieldName']]['value'] = $this->prevStructure;
                $v['value'] = $this->prevStructure;
            }

            // Если в значении NULL, то сохранять это поле не надо
            if ($v['value'] === null) {
                continue;
            }

            $groups[$group][$field] = $v['value'];
        }
        unset($groups[$groupName]['ID']);

        $db = Db::getInstance();

        $id = $db->insert($this->_table, $groups[$groupName]);

        if ($id != false) {
            $result['items'][$groupName . '_ID']['value'] = $id;
            $groups[$groupName]['ID'] = $id;

            if (isset($result['sqlAdd'][$groupName]) && ($result['sqlAdd'][$groupName] != '')) {
                $sqlAdd = str_replace('{{ table }}', $this->_table, $result['sqlAdd'][$groupName]);
                $sqlAdd = str_replace('{{ objectId }}', $id, $sqlAdd);
                $sqlAdd = explode(';', $sqlAdd);
                foreach ($sqlAdd as $_sql) {
                    if ($_sql != '') {
                        $db->query($_sql);
                    }
                }
            }

            $result = $this->saveAddData($result, $groups, $groupName, true);
        } else {
            // Добавить запись не получилось
            $result['isCorrect'] = 0;
            $result['errorText'] = 'Ошибка при добавлении в БД. ' . $db->error;
        }
        return $result;
    }

    /**
     * Обработка переменных от дополнительных табов с аддонами
     *
     * @param array $result
     * @param array $groups
     * @param string $groupName
     * @param bool $isCreate
     * @return array
     */
    public function saveAddData($result, $groups, $groupName, $isCreate = false)
    {
        $config = Config::getInstance();

        // Считываем данные дополнительных табов
        foreach ($this->fields as $fieldName => $field) {
            if (strpos($field['type'], '_Addon') === false) {
                continue;
            }

            $addonsInfo = json_decode($groups[$groupName][$fieldName]);

            // Сохраняем информацию из аддонов
            foreach ($addonsInfo as $addonInfo) {
                $tempAddonInfo = explode('_', $addonInfo[1]);
                $addonGroupName = strtolower(end($tempAddonInfo)) . '-' . $addonInfo[0];
                $addonData = $groups[$addonGroupName];
                $end = end($this->path);
                $prevStructure = $config->getStructureByName($end['structure']);

                // значение преструктуры основной структуры
                // TODO переделать собирание преструктуры, чтобы значение брались из правильного места
                $addonData['prev_structure'] = $prevStructure['ID'] . '-' . $groups[$groupName]['ID'];
                if (empty($addonData['ID'])) {
                    // Для случая, если вдруг элемент был создан, а аддон у него был непрописан
                    $isCreate = true;
                }
                if ($isCreate) {
                    unset($addonData['ID']);
                }

                $addonModelName = Util::getClassName($addonInfo[1], 'Addon') . '\\Model';

                /* @var $addonModelName \Ideal\Core\Admin\Model */
                $addonModel = new $addonModelName($addonData['prev_structure']);
                if ($isCreate) {
                    // Записываем данные шаблона в БД и в $result
                    $result = $addonModel->createElement($result, $addonGroupName);
                } else {
                    $addonModel->setPageDataById($addonData['ID']);
                    $result = $addonModel->saveElement($result, $addonGroupName);
                }
            }


            // Удаляем информацию об удалённых аддонах
            $pageData = $this->getPageData();
            $preSaveAddonsInfo = (isset($pageData['addon'])) ? json_decode($pageData['addon']) : array();
            if (!empty($preSaveAddonsInfo)) {
                foreach ($preSaveAddonsInfo as $key => $preSaveAddonInfo) {
                    // Удаляем информацию об аддоне из старого списка, если его нет в новом.
                    if (!in_array($preSaveAddonInfo, $addonsInfo)) {
                        $tempPreSaveAddonInfo = explode('_', $preSaveAddonInfo[1]);
                        $preSaveAddonGroupName = strtolower(end($tempPreSaveAddonInfo)) . '-' . $preSaveAddonInfo[0];
                        $end = end($this->path);
                        $preSaveAddonPrevStructure = $config->getStructureByName($end['structure']);

                        // значение преструктуры основной структуры
                        // TODO переделать собирание преструктуры, чтобы значение брались из правильного места
                        $preSaveAddonDataPrevStructure = $preSaveAddonPrevStructure['ID']
                            . '-' . $groups[$groupName]['ID'];
                        $addonModelName = Util::getClassName($preSaveAddonInfo[1], 'Addon') . '\\Model';

                        /* @var $addonModelName \Ideal\Core\Admin\Model */
                        $preSaveAddonModel = new $addonModelName($preSaveAddonDataPrevStructure);
                        $preSaveAddonModel->setFieldsGroup($preSaveAddonGroupName);
                        $preSaveAddonModel->setPageDataByPrevStructure($preSaveAddonDataPrevStructure);
                        // Удаляем данные об аддоне
                        $preSaveAddonModel->delete();
                    }
                }
            }
        }
        return $result;
    }

    public function saveElement($result, $groupName = 'general')
    {
        // Из общего списка введённых данных выделяем те, что помечены general
        foreach ($result['items'] as $v) {
            list($group, $field) = explode('_', $v['fieldName'], 2);

            if ($group == $groupName && $field == 'prev_structure' && $v['value'] == '') {
                $result['items'][$v['fieldName']]['value'] = $this->prevStructure;
                $v['value'] = $this->prevStructure;
            }

            // Если в значении NULL, то сохранять это поле не надо
            if ($v['value'] === null) {
                continue;
            }

            $groups[$group][$field] = $v['value'];
        }

        $db = Db::getInstance();

        $db->update($this->_table)->set($groups[$groupName]);
        $db->where('ID = :id', array('id' => $groups[$groupName]['ID']))->exec();
        if ($db->errno > 0) {
            // Если при попытке обновления произошла ошибка не выполнять доп. запросы, а сообщить об этом пользователю
            $result['isCorrect'] = false;
            $result['errorText'] = $db->error . PHP_EOL . 'Query: ' . $db->exec(false);
            return $result;
        }

        if (isset($result['sqlAdd'][$groupName]) && ($result['sqlAdd'][$groupName] != '')) {
            $sqlAdd = str_replace('{{ table }}', $this->_table, $result['sqlAdd'][$groupName]);
            $sqlAdd = str_replace('{{ objectId }}', $groups[$groupName]['ID'], $sqlAdd);
            $sqlAdd = explode(';', $sqlAdd);
            foreach ($sqlAdd as $_sql) {
                if ($_sql != '') {
                    $db->query($_sql);
                }
            }
        }

        $result = $this->saveAddData($result, $groups, $groupName);

        return $result;
    }

    public function detectPageByIds($path, $par)
    {
        throw new \Exception('Попытка вызвать непереопределённый метод detectPageByIds в классе ' . get_class($this));
    }

    public function getFieldsList($tab)
    {
        $tabsContent = '';
        foreach ($tab as $fieldName => $field) {
            $fieldClass = Util::getClassName($field['type'], 'Field') . '\\Controller';
            /* @var $fieldModel \Ideal\Field\AbstractController */
            $fieldModel = $fieldClass::getInstance();
            $fieldModel->setModel($this, $fieldName, $this->fieldsGroup);
            $tabsContent .= $fieldModel->showEdit();
        }
        return $tabsContent;
    }

    public function getHeaderNames()
    {
        $headers = $this->getHeaders();

        $headerNames = array();

        // Составляем список названий колонок
        foreach ($headers as $v) {
            $headerNames[$v] = $this->fields[$v]['label'];
        }

        return $headerNames;
    }

    public function getHeaders()
    {
        $headers = array();

        // Убираем символы ! из заголовков
        foreach ($this->params['field_list'] as $v) {
            $column = explode('!', $v);
            $headers[] = $column[0];
        }

        return $headers;
    }

    public function getTitle()
    {
        $config = Config::getInstance();

        $title = $this->getHeader() . ' - админка ' . $config->domain;

        return $title;
    }

    public function getHeader()
    {
        $end = end($this->path);
        return $end['name'];
    }

    public function getToolbar()
    {
        return '';
    }

    /**
     * Если всё правильно - возвращает массив для сохранения,
     * если неправильно - массив с сообщениями об ошибках.
     *
     * @param bool $isCreate
     * @return array|bool
     * @throws \Exception
     */
    public function parseInputParams($isCreate = false)
    {
        $result = array(
            'isCorrect' => true,
            'errorTabs' => array(),
            'items' => array()
        );

        // Для каждого поля прописываем имя вкладки, в которой оно находится
        $tabs = array('tab1');
        foreach ($this->fields as $fieldName => $field) {
            if ($this->fieldsGroup != 'general') {
                // Пока на каждый шаблон можно использовать только одну вкладку
                $this->fields[$fieldName]['realTab'] = $this->fieldsGroup;
                continue;
            }
            // Для каждой записи в структуре может быть несколько вкладок
            $tab = 'tab1';
            if (isset($field['tab'])) {
                if (!array_key_exists($field['tab'], $tabs)) {
                    $tabs[$field['tab']] = 'tab' . ((int) substr(end($tabs), 3) + 1);
                }
                $tab = $tabs[$field['tab']];
            }
            $this->fields[$fieldName]['realTab'] = $tab;
        }

        $result['sqlAdd'][$this->fieldsGroup] = '';

        // Проходимся по всем полям этого типа и проверяем их корректность
        foreach ($this->fields as $fieldName => $field) {
            // TODO добавить валидаторы

            // Определеям класс контроллера для соответствующего поля
            $fieldClass = Util::getClassName($field['type'], 'Field') . '\\Controller';
            /* @var $fieldModel \Ideal\Field\AbstractController */
            $fieldModel = $fieldClass::getInstance();
            $fieldModel->setModel($this, $fieldName, $this->fieldsGroup);
            // Получаем данные, введённые пользователем
            $item = $fieldModel->parseInputValue($isCreate);

            if (isset($item['items'])) {
                // Если есть вложенные элементы - добавляем их к результатам
                // Проверяем на наличие нескольких вложенностей
                if (is_array($item['items']) && !isset($item['items']['items'])) {
                    foreach ($item['items'] as $value) {
                        $result['items'] = array_merge($result['items'], $value['items']);

                        // Добавляем дополнительные запросы от вложенных элементов
                        $result['sqlAdd'] = array_merge($result['sqlAdd'], $value['sqlAdd']);
                        if (!$value['isCorrect']) {
                            $result['isCorrect'] = false;
                        }
                    }
                } else {
                    $result['items'] = array_merge($result['items'], $item['items']['items']);
                    if (!$item['items']['isCorrect']) {
                        $result['isCorrect'] = false;
                    }
                }
                unset($item['items']);
            }

            if (!isset($item['sqlAdd'])) {
                // Свойство sqlAdd должно быть обязательно определено для каждого редактируемого поля
                throw new \Exception('Отсутствует свойство sqlAdd в поле ' . print_r($item, true));
            }

            $result['sqlAdd'][$this->fieldsGroup] .= $item['sqlAdd'];

            $item['realTab'] = $field['realTab'];
            $result['items'][$item['fieldName']] = $item;
        }

        // Проверяем все поля на ошибки, если ошибки есть — составляем список табов, в которых ошибки
        foreach ($result['items'] as $fieldName => $item) {
            // Если есть сообщение об ошибке - значит общий результат - ошибка
            $result['isCorrect'] = (($item['message'] === '') && ($result['isCorrect'] == true));

            // Составляем список вкладок, в которых возникли ошибки
            if (($item['message'] !== '') && (!in_array($item['realTab'], $result['errorTabs']))) {
                $result['errorTabs'][] = $item['realTab'];
            }
        }

        return $result;
    }

    public function setFieldsGroup($name)
    {
        $this->fieldsGroup = $name;
    }

    /**
     * Установка пустого pageData
     */
    public function setPageDataNew()
    {
        $this->setPageData(array());
    }

    public function delete()
    {
        $config = Config::getInstance();
        $pageData = $this->getPageData();

        // Если есть подключенные аддоны, то сперва удаляем информацию из их таблиц
        if (isset($pageData['addon']) && !empty($pageData['addon'])) {
            $addonsInfo = json_decode($pageData['addon']);

            $end = end($this->path);
            $prevStructure = $config->getStructureByName($end['structure']);
            $addonDataPrevStructure = $prevStructure['ID'] . '-' . $pageData['ID'];

            foreach ($addonsInfo as $addonInfo) {
                list(, $sliceAddonTableName) = explode('_', $addonInfo[1]);
                $sliceAddonTableName = strtolower($sliceAddonTableName);
                $tableName = $config->db['prefix'] . 'ideal_addon_' . $sliceAddonTableName;
                $db = Db::getInstance();
                $db->delete($tableName)->where('prev_structure=:ps', array('ps' => $addonDataPrevStructure));
                $db->exec();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getWhere($where)
    {
        // Добавляем проверку на скрытие части страниц с помощью прав доступа
        $config = Config::getInstance();
        $structure = $config->getStructureByClass(get_class($this));
        $user = \Ideal\Structure\User\Model::getInstance();
        $aclTable = $config->db['prefix'] . 'ideal_structure_acl';
        $sqlAcl = "SELECT structure FROM {$aclTable} WHERE user_group_id='{$user->data['user_group']}' AND `show`=0";
        $where .= " AND CONCAT('{$structure['ID']}-', e.ID) NOT IN ({$sqlAcl})";

        return parent::getWhere($where);
    }

    /**
     * Получение списка элементов с наложением списка прав доступа
     *
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getListAcl($page)
    {
        $config = Config::getInstance();
        $structure = $config->getStructureByClass(get_class($this));
        $list = $this->getList($page);
        $ids = array();
        foreach ($list as $k => $v) {
            $ids[$v['ID']] = $structure['ID'] . '-' . $v['ID'];
        }
        $aclModel = new \Ideal\Structure\Acl\Admin\Model();
        $acl = $aclModel->getAcl($ids);
        foreach ($list as $k => $v) {
            if (!empty($acl[$ids[$v['ID']]])) {
                $list[$k]['acl'] = $acl[$ids[$v['ID']]];
            }
        }
        return $list;
    }
}
