<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\ContactPerson\Admin;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;
use Ideal\Structure\Order\Admin\Model as OrderModel;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{

    public function saveElement($result, $groupName = 'general')
    {
        $result = $this->clearFields($result, $groupName);
        if (!isset($result['items'][$groupName . '_existingСontactPerson']['value']) ||
            !$result['items'][$groupName . '_existingСontactPerson']['value']
        ) {
            return parent::saveElement($result, $groupName);
        } else {
            // Если выбран существующий контакт, то сохранять поля не нужно
            return '';
        }
    }

    public function createElement($result, $groupName = 'general')
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        // Получаем связанные данные
        $relatedData = '';
        if (isset($result['items'][$groupName . '_relatedData']) &&
            isset($result['items'][$groupName . '_relatedData']['value'])
        ) {
            $relatedData = $result['items'][$groupName . '_relatedData']['value'];
        }

        // Если выбрано существующее контактное лицо, то берём его
        if ($result['items'][$groupName . '_existingСontactPerson']['value']) {
            $par = array('ID' => (int) $result['items'][$groupName . '_existingСontactPerson']['value']);
            $fields = array('table' => $this->_table);
            $rows = $db->select('SELECT * FROM &table WHERE ID = :ID', $par, $fields);
            if (!$rows) {
                throw new \Exception('Выбранного контактного лица не существует');
            }
            $result['responseMessage'] = 'Заказ успешно отнесён к контактному лицу';
            $this->setPageData($rows[0]);
        } else {
            $result = $this->clearFields($result, $groupName);
            $result = parent::createElement($result, $groupName);
            $pageData = $this->getPageData();

            // Создаём новый лид при создании контактного лица
            $leadStructure = $config->getStructureByName('Ideal_Lead');
            $leadTable = $config->getTableByName('Ideal_Lead');
            $leadId = $db->insert($leadTable, array(
                'addon' => '[["1","Ideal_ContactPerson","' . $pageData['name'] . '"]]'));

            // Делаем запись в таблице аддона "Контактное лицо"
            $contactPersonAddonTable = $config->getTableByName('Ideal_ContactPerson', 'Addon');
            $values = array(
                'prev_structure' => $leadStructure['ID'] . '-' . $leadId,
                'tab_ID' => 1,
                'contact_person' => $pageData['ID'],
            );
            $db->insert($contactPersonAddonTable, $values);
        }

        // Привязываем контактное лицо к заказу
        if ($relatedData) {
            $relatedData = explode('-', $relatedData);
            if (isset($relatedData[0]) && $relatedData[0] == 'orderId') {
                $orderTable = $config->getTableByName('Ideal_Order');
                $pageData = $this->getPageData();
                $values = array('contact_person' => $pageData['ID']);
                $sql = 'ID = :ID';
                $params = array('ID' => (int)$relatedData[1]);
                $db->update($orderTable)->set($values)->where($sql, $params)->exec();
            }
        }

        return $result;
    }

    /**
     * Установка пустого pageData.
     * Либо установка начальных данных по данным заказа.
     */
    public function setPageDataNew()
    {
        $request = new Request();
        $pageData = array();

        // Проверяем наличие переданных связанных данных
        $relatedData = $request->relatedData;
        if ($relatedData) {
            $pageData['relatedData'] = $relatedData;
            // Разбираем строку переданых связанных данных
            $relatedData = explode('-', $relatedData);
            if (isset($relatedData[0]) && $relatedData[0] == 'orderId') {
                $order = new OrderModel('');
                $order->setPageDataById((int)$relatedData[1]);
                $orderPageData = $order->getPageData();
                if ($orderPageData['name']) {
                    $pageData['name'] = $orderPageData['name'];
                }
                if ($orderPageData['email']) {
                    $pageData['email'] = $orderPageData['email'];
                }
                if ($orderPageData['phone']) {
                    $pageData['phone'] = $orderPageData['phone'];
                }
            }
        }
        $pageData['lead'] = 0;
        $this->setPageData($pageData);
    }

    public function parseInputParams($isCreate = false)
    {
        $result = parent::parseInputParams($isCreate);

        // При сохранении контактного лица через аддоны идентификатор может находиться в другом поле,
        // проверяем его наличие и при надобности подменяем значение идентификатора
        $request = new Request();
        $requestName = $this->fieldsGroup . '_CPID';
        $cotPersonId = $request->$requestName;
        if ($cotPersonId) {
            $result['items'][$this->fieldsGroup . '_ID']['value'] = $cotPersonId;
        }
        return $result;
    }

    /**
     * Убирает поля "Выбрать существующий контакт" и "Связанные данные"
     *
     * @param $result array Данные с формы
     * @param $groupName string Нсзвание группы полей
     * @return array очищенный от полей которые не нужно записывать в базу
     */
    private function clearFields($result, $groupName)
    {
        if (isset($result['items'][$groupName . '_existingСontactPerson'])) {
            unset($result['items'][$groupName . '_existingСontactPerson']);
        }
        if (isset($result['items'][$groupName . '_relatedData'])) {
            unset($result['items'][$groupName . '_relatedData']);
        }
        return $result;
    }
}
