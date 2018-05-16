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
use Ideal\Core\Request;
use Ideal\Structure\Order\Admin\Model as OrderModel;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{

    public function saveElement($result, $groupName = 'general')
    {
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
        $result['items']['general_join_with_customer']['value'] = null;
        return parent::createElement($result, $groupName);
    }

    /**
     * Установка пустого pageData.
     * Либо установка начальных данных по даннмы заказа.
     */
    public function setPageDataNew()
    {
        $request = new Request();
        $pageData = array();

        // Проверяем наличие переданных связанных данных
        $relatedData = $request->relatedData;
        if ($relatedData) {
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
}
