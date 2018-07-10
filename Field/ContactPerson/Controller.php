<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\ContactPerson;

use Ideal\Core\Config;
use Ideal\Core\Db;
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
        if (!$this->newValue) {
            $fieldName = $this->groupName . '_existingСontactPerson';
            $this->newValue = $request->$fieldName;
        }
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
                // Если выбрано существующее конттактное лицо то делаем дополнительный запрос на замену названия вкладки
                if (isset($result['items'][$this->groupName . '_existingСontactPerson']) &&
                    !empty($result['items'][$this->groupName . '_existingСontactPerson']['value'])
                ) {
                    $config = Config::getInstance();
                    $db = Db::getInstance();
                    $parentModel = $this->model->getParentModel();
                    $parentModelTable = $parentModel->getTableName();
                    $parentModelPageData = $parentModel->getPageData();
                    $contactPersonTable = $config->getTableByName('Ideal_ContactPerson');
                    $contactPersonId = $result['items'][$this->groupName . '_existingСontactPerson']['value'];
                    $sqlToSelectName = "SELECT name FROM {$contactPersonTable} WHERE ID = {$contactPersonId}";
                    $contactPersonName = $db->select($sqlToSelectName);
                    $contactPersonName = $contactPersonName[0]['name'];
                    $item['sqlAdd'] = "UPDATE {$parentModelTable} SET addon = CONCAT(REPLACE(";
                    $item['sqlAdd'] .= "LEFT(addon, INSTR(addon, 'Контактное лицо') +";
                    $item['sqlAdd'] .= " LENGTH('Контактное лицо') - 1),'Контактное лицо', '{$contactPersonName}'),";
                    $item['sqlAdd'] .= "SUBSTRING(addon, INSTR(addon, 'Контактное лицо') + LENGTH('Контактное лицо')))";
                    $item['sqlAdd'] .= " WHERE INSTR(addon, 'Контактное лицо') AND ID = {$parentModelPageData['ID']};";
                } else {
                    $this->contactPersonModel->saveElement($result, $this->groupName);
                    $this->contactPersonModel->saveToLog('Изменён');
                }
            }
        }
        return $item;
    }
}
