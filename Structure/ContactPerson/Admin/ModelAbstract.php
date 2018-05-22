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
        if (isset($result['items']['general_join_with_customer']) && $result['items']['general_join_with_customer']) {
            // Объединение телефонов, электронных адресов и Client Id
            $recipientId = $result['items']['general_join_with_customer']['value'];
            $result['sqlAdd'][$groupName] = '
            UPDATE 
              {{ table }} as r 
            LEFT JOIN {{ table }} as d 
            ON d.ID = {{ objectId }} 
            SET r.emails = REPLACE(CONCAT(r.emails, d.emails), \'][\', \',\'),
            r.client_ids = REPLACE(CONCAT(r.client_ids, d.client_ids), \'][\', \',\'),
            r.phones = REPLACE(CONCAT(r.phones, d.phones), \'][\', \',\') 
            WHERE r.ID = ' . $recipientId;

            // Переводим все заказы донора на реципиента
            // Проверяем наличие подключенной структуры заказов.
            // Считаем что при наличии подключенной структуры существует и таблица
            $config = Config::getInstance();
            if ($config->getStructureByName('Ideal_Order')) {
                $orderTable = $config->getTableByName('Ideal_Order');
                $result['sqlAdd'][$groupName] .= ';
                UPDATE 
                    ' . $orderTable . ' 
                SET customer = ' . $recipientId . ' 
                WHERE customer = {{ objectId }}';
            }

            // Удаляем заказчика-донора при объединении
            $result['sqlAdd'][$groupName] .= ';
            DELETE FROM {{ table }} WHERE ID = {{ objectId }}';
        }

        // Значение поля сохранять в базу не требуется
        $result['items']['general_join_with_customer']['value'] = null;
        return parent::saveElement($result, $groupName);
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
            // Создаём новый лид при создании контактного лица
            $leadTable = $config->getTableByName('Ideal_Lead');
            $leadId = $db->insert($leadTable, array());

            // Привязываем новое контактное лицо к только-что созданному лиду
            $result["items"][$groupName . "_lead"]["value"] = $leadId;
            $result = $this->clearFields($result, $groupName);
            $result = parent::createElement($result, $groupName);
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
