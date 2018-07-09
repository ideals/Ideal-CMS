<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\ContactPerson;

use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Field\AbstractController;
use Ideal\Structure\ContactPerson\Admin\Model;

/**
 * Поле, проксирующее редактирование структуры "Контактное лицо" на вкладке аддона "Контактное лицо" структуры "Лид".
 */
class Controller extends AbstractController
{
    protected $contactPersonModel;

    public function __construct()
    {
        $this->contactPersonModel = new Model('');
    }

    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function showEdit()
    {
        $this->htmlName = $this->groupName . '_' . $this->name;
        $input = $this->getInputText();
        return $input;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $pageData = $this->model->getPageData();
        $this->contactPersonModel->setPageDataById($pageData['ID']);
        $fields = $this->contactPersonModel->getFieldsList($this->contactPersonModel->fields);

        // Формируем правильные имена и идентификаторы для полей внутри таба
        $fields = str_replace('general', $this->groupName, $fields);
        return $fields;
    }

    public function pickupNewValue()
    {
        parent::pickupNewValue();
        $request = new Request();
        $fieldName = $this->groupName . '_ID';
        $this->newValue = $request->$fieldName;
        return $this->newValue;
    }

    public function parseInputValue($isCreate)
    {
        $item = parent::parseInputValue($isCreate);

        // Формируем запрос на обновление данных контактного лица
        $this->contactPersonModel->setPageDataById($item["value"]);
        $contactPersonPageData = $this->contactPersonModel->getPageData();
        $contactPersonTable = $this->contactPersonModel->getTableName();
        $updateFieldStrings= array();
        foreach ($this->contactPersonModel->fields as $fieldName => $field) {
            // Определеям класс контроллера для соответствующего поля
            $fieldClass = Util::getClassName($field['type'], 'Field') . '\\Controller';
            /* @var $fieldModel \Ideal\Field\AbstractController */
            $fieldModel = $fieldClass::getInstance();
            $fieldModel->setModel($this->contactPersonModel, $fieldName, $this->groupName);
            // Получаем данные, введённые пользователем
            $fieldItem = $fieldModel->parseInputValue($isCreate);

            if (isset($contactPersonPageData[$fieldName]) &&
                $fieldItem['value'] != $contactPersonPageData[$fieldName]
            ) {
                $updateFieldStrings[] = "{$fieldName}='{$fieldItem['value']}'";
            }
        }
        if (!empty($updateFieldStrings)) {
            $updateString = ' SET ' . implode(', ', $updateFieldStrings);
            $item['sqlAdd'] = "UPDATE {$contactPersonTable}{$updateString}";
        }
        return $item;
    }
}
