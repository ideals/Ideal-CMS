<?php
namespace Ideal\Core\Admin;

use Ideal\Core;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Util;

abstract class Model extends Core\Model
{
    protected $fieldsGroup = 'general';

    /**
     * Заполнение pageData пустыми значениями полей
     */
    public function setPageDataNew()
    {
        $pageData = array();
        foreach($this->fields as $fieldName => $field) {
            $pageData[$fieldName] = '';
        }
        $this->setPageData($pageData);
    }

    public function detectPageByIds($path, $par)
    {
        throw new \Exception('Попытка вызвать непереопределённый метод detectPageByIds в классе ' . get_class($this));
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


    public function setFieldsGroup($name)
    {
        $this->fieldsGroup = $name;
    }


    /**
     * Если всё правильно - возвращает массив для сохранения,
     * если неправильно - массив с сообщениями об ошибках.
     * @param $isCreate
     * @return array | bool
     */
    public function parseInputParams($isCreate = false)
    {
        $result = array(
            'isCorrect' => true,
            'errorTabs' => array(),
            'items'     => array()
        );

        // Для каждого поля прописываем имя вкладки, в которой оно находится
        $tabs = array('tab1');
        foreach($this->fields as $fieldName => $field) {
            if ($this->fieldsGroup != 'general') {
                // Пока на каждый шаблон можно использовать только одну вкладку
                $this->fields[$fieldName]['realTab'] = $this->fieldsGroup;
                continue;
            }
            // Для каждой записи в структуре может быть несколько вкладок
            $tab = 'tab1';
            if (isset($field['tab'])) {
                if (!array_key_exists($field['tab'], $tabs)) {
                    $tabs[$field['tab']] = 'tab' . (substr(end($tabs), 3) + 1);
                }
                $tab = $tabs[$field['tab']];
            }
            $this->fields[$fieldName]['realTab'] = $tab;
        }

        $result['sqlAdd'][$this->fieldsGroup] = '';

        // Проходимся по всем полям этого типа и проверяем их корректность
        foreach ($this->fields as $fieldName => $field) {
            // TODO добавить валидаторы

            $fieldClass = Util::getClassName($field['type'], 'Field') . '\\Controller';
            /* @var $fieldModel \Ideal\Field\AbstractController */
            $fieldModel = $fieldClass::getInstance();
            $fieldModel->setModel($this, $fieldName, $this->fieldsGroup);
            $item = $fieldModel->parseInputValue($isCreate);

            if (isset($item['items'])) {
                // Если есть вложенные элементы - добавляем их к результатам
                $result['items'] = array_merge($result['items'], $item['items']['items']);
                if (!$item['items']['isCorrect']) {
                    $result['isCorrect'] = false;
                }
                unset($item['items']);
            }

            $result['sqlAdd'][$this->fieldsGroup] .= $item['sqlAdd'];

            $item['realTab'] = $field['realTab'];
            $result['items'][$item['fieldName']] = $item;
        }

        // Проверяем все поля на ошибки, если ошибки есть — составляем список табов, в которых ошибки
        foreach ($result['items'] as $fieldName => $item) {
            // Если есть сообщение об ошибке - значит общий результат - ошибка
            $result['isCorrect'] = (($item['message'] === '') AND ($result['isCorrect'] == true));

            // Составляем список табов, в которых возникли ошибки
            if (($item['message'] !== '')
                AND (!in_array($item['realTab'], $result['errorTabs']))) {
                $result['errorTabs'][] = $item['realTab'];
            }
        }

        return $result;
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


    public function createElement($result, $groupName = 'general')
    {
        // Из общего списка введённых данных выделяем те, что помечены general
        foreach ($result['items'] as $v) {
            list($group, $field) = explode('_', $v['fieldName'], 2);

            if ($group == $groupName
                AND $field == 'prev_structure' AND $v['value'] == '') {
                $result['items'][$v['fieldName']]['value'] = $this->prevStructure;
                $v['value'] = $this->prevStructure;
            }

            // Если в значении NULL, то сохранять это поле не надо
            if ($v['value'] === NULL) {
                continue;
            }

            $groups[$group][$field] = $v['value'];
        }
        unset($groups[$groupName]['ID']);

        $db = Db::getInstance();

        $id = $db->insert($this->_table, $groups[$groupName]);

        if ($id !== false) {
            $result['items'][$groupName . '_ID']['value'] = $id;
            $groups[$groupName]['ID'] = $id;

            if (isset($result['sqlAdd'][$groupName]) AND ($result['sqlAdd'][$groupName] != '')) {
                $sqlAdd = str_replace('{{ table }}', $this->_table, $result['sqlAdd'][$groupName]);
                $sqlAdd = str_replace('{{ objectId }}', $id, $sqlAdd);
                $sqlAdd = explode(';', $sqlAdd);
                foreach ($sqlAdd as $_sql) {
                    if ($_sql == '') continue;
                    $db->query($_sql);
                }
            }

            $result = $this->saveAddData($result, $groups, $groupName, true);
        } else {
            // Добавить запись не получилось
            $result['isCorrect'] = 0;
            $result['errorText'] = 'Ошибка при добавлении в БД. ' . $db->getLastError();
        }
        return $result;
    }


    public function saveElement($result, $groupName = 'general')
    {
        // Из общего списка введённых данных выделяем те, что помечены general
        foreach ($result['items'] as $v) {
            list($group, $field) = explode('_', $v['fieldName'], 2);

            if ($group == $groupName
                AND $field == 'prev_structure' AND $v['value'] == '') {
                $result['items'][$v['fieldName']]['value'] = $this->prevStructure;
                $v['value'] = $this->prevStructure;
            }

            // Если в значении NULL, то сохранять это поле не надо
            if ($v['value'] === NULL) {
                continue;
            }

            $groups[$group][$field] = $v['value'];
        }

        $db = Db::getInstance();

        $db->update($this->_table, $groups[$groupName]['ID'], $groups[$groupName]);

        if (isset($result['sqlAdd'][$groupName]) AND ($result['sqlAdd'][$groupName] != '')) {
            $sqlAdd = str_replace('{{ table }}', $this->_table, $result['sqlAdd'][$groupName]);
            $sqlAdd = str_replace('{{ objectId }}', $groups[$groupName]['ID'], $sqlAdd);
            $sqlAdd = explode(';', $sqlAdd);
            foreach ($sqlAdd as $_sql) {
                if ($_sql == '') continue;
                $db->query($_sql);
            }
        }

        $result = $this->saveAddData($result, $groups, $groupName);

        return $result;
    }


    /**
     * Обработка переменных от дополнительных табов с шаблонами
     * @param array $result
     * @param array $groups
     * @param string $groupName
     * @param bool $isCreate
     * @return array
     */
    function saveAddData($result, $groups, $groupName, $isCreate = false)
    {
        $config = Config::getInstance();

        // Считываем данные дополнительных табов
        foreach ($this->fields as $fieldName => $field) {
            if (strpos($field['type'], '_Template') === false) continue;

            $templateData = $groups[$fieldName];
            $prevStructure = $config->getStructureByPrev($groups[$groupName]['prev_structure']);
            $templateData['prev_structure'] = $prevStructure['ID'] . '-' . $groups[$groupName]['ID'];
            if (empty($templateData['ID'])) {
                // Для случая, если вдруг элемент был создан, а шаблон у него был непрописан
                $isCreate = true;
            }
            if ($isCreate) {
                unset($templateData['ID']);
            }

            $templateModelName = Util::getClassName($groups[$groupName][$fieldName], 'Template') . '\\Model';

            /* @var $templateModel \Ideal\Core\Admin\Model */
            $templateModel = new $templateModelName($templateData['prev_structure']);
            if ($isCreate) {
                // Записываем данные шаблона в БД и в $result
                $result = $templateModel->createElement($result, $fieldName);
            } else {
                $templateModel->setPageDataById($groups[$fieldName]['ID']);
                $result = $templateModel->saveElement($result, $fieldName);
            }
        }
        return $result;
    }


    public function checkTemplateChange($result)
    {
        foreach ($result['items'] as $fieldName => $field) {
            $result['items'][$fieldName]['confirm'] = '';
            $fieldsGroup = $this->fieldsGroup . '_';
            if (substr($fieldName, 0, strlen($fieldsGroup)) != $fieldsGroup) {
                continue;
            }
            $realName = substr($fieldName, strlen($fieldsGroup));
            if (!isset($this->fields[$realName])) {
                continue;
            }
            if ($this->fields[$realName]['type'] != 'Template') {
                continue;
            }
            if ($this->pageData[$realName] == '') {
                // Если изначально шаблон не был задан - просто сохраняем введённое значение
                continue;
            }
            if ($field['value'] != $this->pageData[$realName]) {
                $result['isCorrect'] = 2;

                $oldTemplateName = Util::getClassName($this->pageData[$realName], 'Template') . '\\Model';
                $oldTemplate = new $oldTemplateName($this->pageData[$realName], '');
                $oldTemplateCap = $oldTemplate->params['name'];

                $newTemplateName = Util::getClassName($field['value'], 'Template') . '_Model';
                $newTemplate = new $newTemplateName($field['value'], '');
                $newTemplateCap = $newTemplate->params['name'];

                $result['items'][$fieldName]['confirm'] = 'шаблон «'
                                                        . $this->fields[$realName]['label']
                                                        . '» с «' . $oldTemplateCap
                                                        . '» на «' . $newTemplateCap . '»';
            }
        }
        return $result;
    }


    public function getHeader()
    {
        $end = end($this->path);
        return $end['name'];
    }


    public function getTitle()
    {
        $config = Config::getInstance();

        $title =  $this->getHeader() . ' - админка ' . $config->domain;

        return $title;
    }


    public function getToolbar()
    {
        return '';
    }
}