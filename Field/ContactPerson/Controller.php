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
        $fields = $this->contactPersonModel->fields;
        if (isset($pageData['contact_person'])) {
            $this->contactPersonModel->setPageDataById($pageData['contact_person']);

            // Если присутствует идентификатор контактного лица, то убираем поле выбора существующего контактного лица
            unset($fields['existingСontactPerson']);
        }
        $fields = $this->contactPersonModel->getFieldsList($fields);

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
        if (!empty($item["value"])) {
            $this->contactPersonModel->setPageDataById($item["value"]);
            $this->contactPersonModel->setFieldsGroup($this->groupName);
            $result = $this->contactPersonModel->parseInputParams();
            $aclModel = new \Ideal\Structure\Acl\Admin\Model();
            // Проверяем, есть ли право редактирования элемента
            if ($result['isCorrect'] == 1) {
                $result['isCorrect'] = $aclModel->checkAccess($this->contactPersonModel, 'edit');
            }

            if ($result['isCorrect'] == 1) {
                $this->contactPersonModel->saveElement($result, $this->groupName);
                $this->contactPersonModel->saveToLog('Изменён');
            }
        }
        return $item;
    }
}
