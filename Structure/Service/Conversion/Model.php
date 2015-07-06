<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\Conversion;

use \Ideal\Core\Db;
use \Ideal\Core\Config;

/**
 * Класс для получения и обработки данных о заказах с их последующим отображением на графиках
 *
 */
class Model
{

    /**
     * Получает конфигурационные данные для всех графиков
     *
     * @param integer $fromTimestamp Дата с которой начинать собирать информацию
     * @param integer $toTimestamp Дата до которой нужно собрать информацию
     * @param string $interval Строковое представление временного интервала для отображения на графике
     * @return array Массив с конфигурационными строками для графиков.
     */
    public function getOrdersInfo($fromTimestamp, $toTimestamp, $interval = 'day')
    {
        $visualConfig['quantityOfOrders'] = $this->getQuantityOfOrdersInfo($fromTimestamp, $toTimestamp, $interval);
        $visualConfig['referer'] = $this->getRefererOrdersInfo($fromTimestamp, $toTimestamp);
        $visualConfig['orderType'] = $this->getOrderTypeInfo($fromTimestamp, $toTimestamp);
        $visualConfig['sumOfOrder'] = $this->getSumOfOrdersInfo($fromTimestamp, $toTimestamp, $interval);
        return $visualConfig;
    }

    /**
     * Получает конфигурационные данные для первого графика
     *
     * @param integer $fromTimestamp Дата с которой начинать собирать информацию
     * @param integer $toTimestamp Дата до которой нужно собрать информацию
     * @param string $interval Строковое представление временного интервала для отображения на графике
     * @return string Строка содержащая конфигурационные данные для первого графика.
     */
    public function getQuantityOfOrdersInfo($fromTimestamp, $toTimestamp, $interval = 'day')
    {
        $visualConfig = '';

        // Задаём интервал для распределения заказов
        switch ($interval) {
            case 'day':
                $interval = 86400;
                break;
            case 'week':
                $interval = 604800;
                break;
            case 'month':
                $interval = 2592000;
                break;
        }

        $db = Db::getInstance();
        $config = Config::getInstance();
        $par = array('fromDate' => $fromTimestamp, 'toDate' => $toTimestamp);
        $fields = array('table' => $config->db['prefix'] . 'ideal_structure_order');
        $row = $db->select('SELECT * FROM &table WHERE date_create >= :fromDate AND date_create <= :toDate ORDER BY date_create ', $par, $fields);

        // Запускаем процесс построения строки/js-массива для настройки отображения первого графика
        if (count($row) > 0) {
            $visualConfig .= "[['Section', 'Яндекс', 'Google', 'Другие сайты', 'Прямой заход', { role: 'annotation' }],";

            $groupedOrders = array();

            // Берём первй день из списка заказов
            $date = $row[0]['date_create'];
            $nextDate = $date + $interval;
            $date = date('d.m.Y', $date);

            // Разбиваем заказы по датам с заданным интервалом
            foreach ($row as $order) {
                if ($order['date_create'] < $nextDate) {
                    $groupedOrders[$date][] = $order['referer'];
                } else {
                    $date = $order['date_create'];
                    $nextDate = $date + $interval;
                    $date = date('d.m.Y', $date);
                    $groupedOrders[$date][] = $order['referer'];
                }
            }

            // Разбиваем даты по реферам
            foreach ($groupedOrders as $key => $ordersInIterval) {
                // Инициализируем группирующие описания рефереров по каждой точке в интервале
                $groupedOrders[$key]['yandex'] = 0;
                $groupedOrders[$key]['google'] = 0;
                $groupedOrders[$key]['other'] = 0;
                $groupedOrders[$key]['straight'] = 0;
                foreach ($ordersInIterval as $refKey => $referer) {
                    // Отлавливаем прямой переход
                    if ($referer == 'null') {
                        $groupedOrders[$key]['straight']++;
                    } elseif (strripos($referer, 'yandex') !== false) { // Отлавливаем яндекс
                        $groupedOrders[$key]['yandex']++;
                    } elseif (strripos($referer, 'google') !== false) { // Отлавливаем гугл
                        $groupedOrders[$key]['google']++;
                    } else { // Отлавливаем другие сайты
                        $groupedOrders[$key]['other']++;
                    }
                    unset($groupedOrders[$key][$refKey]);
                }
            }

            // Собираем строки для js конфигурации
            end($groupedOrders);
            $lastKey = key($groupedOrders);
            foreach ($groupedOrders as $key => $ordersInIterval) {
                $visualConfig .= "['{$key}', {$ordersInIterval['yandex']}, {$ordersInIterval['google']}, {$ordersInIterval['other']}, {$ordersInIterval['straight']}, '']";
                if ($key != $lastKey) {
                    $visualConfig .= ',';
                }
            }
            $visualConfig .= ']';
        }
        return $visualConfig;
    }

    /**
     * Получает конфигурационные данные для второго графика
     *
     * @param integer $fromTimestamp Дата с которой начинать собирать информацию
     * @param integer $toTimestamp Дата до которой нужно собрать информацию
     * @return string Строка содержащая конфигурационные данные для первого графика.
     */
    public function getRefererOrdersInfo($fromTimestamp, $toTimestamp)
    {
        $visualConfig = '';
        $db = Db::getInstance();
        $config = Config::getInstance();
        $par = array('fromDate' => $fromTimestamp, 'toDate' => $toTimestamp);
        $fields = array('table' => $config->db['prefix'] . 'ideal_structure_order');
        $row = $db->select('SELECT * FROM &table WHERE date_create >= :fromDate AND date_create <= :toDate ORDER BY date_create ', $par, $fields);

        if (count($row) > 0) {
            $visualConfig .= "[['Referer', 'Percentage of total'],";
            // Разбиваем заказы по реферам
            // Инициализируем группирующие описания рефереров по каждой точке в интервале
            $groupedOrders = array('yandex' => 0, 'google' => 0, 'other' => 0, 'straight' => 0);
            foreach ($row as $key => $value) {
                // Отлавливаем прямой переход
                if ($value['referer'] == 'null') {
                    $groupedOrders['straight']++;
                } elseif (strripos($value['referer'], 'yandex') !== false) { // Отлавливаем яндекс
                    $groupedOrders['yandex']++;
                } elseif (strripos($value['referer'], 'google') !== false) { // Отлавливаем гугл
                    $groupedOrders['google']++;
                } else { // Отлавливаем другие сайты
                    $groupedOrders['other']++;
                }
            }
            // Собираем строки для js конфигурации
            end($groupedOrders);
            $lastKey = key($groupedOrders);
            foreach ($groupedOrders as $key => $value) {
                $visualConfig .= "['{$key}', {$value}]";
                if ($key != $lastKey) {
                    $visualConfig .= ',';
                }
            }
            $visualConfig .= ']';
        }
        return $visualConfig;
    }

    /**
     * Получает конфигурационные данные для третьего графика
     *
     * @param integer $fromTimestamp Дата с которой начинать собирать информацию
     * @param integer $toTimestamp Дата до которой нужно собрать информацию
     * @return string Строка содержащая конфигурационные данные для первого графика.
     */
    public function getOrderTypeInfo($fromTimestamp, $toTimestamp)
    {
        $visualConfig = '';
        $db = Db::getInstance();
        $config = Config::getInstance();
        $par = array('fromDate' => $fromTimestamp, 'toDate' => $toTimestamp);
        $fields = array('table' => $config->db['prefix'] . 'ideal_structure_order');
        $row = $db->select('SELECT * FROM &table WHERE date_create >= :fromDate AND date_create <= :toDate ORDER BY order_type', $par, $fields);

        if (count($row) > 0) {
            $visualConfig .= "[['Order type', 'Percentage of total'],";
            // Разбиваем заказы по типам
            $groupedOrders = array();
            $orderType = '';
            $orderTypeId = 0;
            foreach ($row as $key => $value) {
                if ($orderType != $value['order_type']) {
                    $orderTypeId++;
                    $orderType = $value['order_type'];
                    $groupedOrders[$orderTypeId]['name'] = $orderType;
                    $groupedOrders[$orderTypeId]['counter'] = 0;
                }
                $groupedOrders[$orderTypeId]['counter']++;
            }
            // Собираем строки для js конфигурации
            end($groupedOrders);
            $lastKey = key($groupedOrders);
            foreach ($groupedOrders as $key => $value) {
                $visualConfig .= "['{$value['name']}', {$value['counter']}]";
                if ($key != $lastKey) {
                    $visualConfig .= ',';
                }
            }
            $visualConfig .= ']';
        }
        return $visualConfig;
    }

    /**
     * Получает конфигурационные данные для четвёртого графика
     *
     * @param integer $fromTimestamp Дата с которой начинать собирать информацию
     * @param integer $toTimestamp Дата до которой нужно собрать информацию
     * @param string $interval Строковое представление временного интервала для отображения на графике
     * @return string Строка содержащая конфигурационные данные для первого графика.
     */
    public function getSumOfOrdersInfo($fromTimestamp, $toTimestamp, $interval = 'day')
    {
        $visualConfig = '';

        // Задаём интервал для распределения заказов
        switch ($interval) {
            case 'day':
                $interval = 86400;
                break;
            case 'week':
                $interval = 604800;
                break;
            case 'month':
                $interval = 2592000;
                break;
        }
        $db = Db::getInstance();
        $config = Config::getInstance();
        $par = array('fromDate' => $fromTimestamp, 'toDate' => $toTimestamp);
        $fields = array('table' => $config->db['prefix'] . 'ideal_structure_order');
        $row = $db->select('SELECT * FROM &table WHERE date_create >= :fromDate AND date_create <= :toDate ORDER BY date_create', $par, $fields);

        if (count($row) > 0) {
            $groupedOrders = array();

            // Берём первй день из списка заказов
            $date = $row[0]['date_create'];
            $nextDate = $date + $interval;
            $date = date('d.m.Y', $date);

            // Разбиваем заказы по датам с заданным интервалом
            foreach ($row as $order) {
                if ($order['date_create'] < $nextDate) {
                    $groupedOrders[$date] += $order['price'];
                } else {
                    $date = $order['date_create'];
                    $nextDate = $date + $interval;
                    $date = date('d.m.Y', $date);
                    $groupedOrders[$date] = intval($order['price']);
                }
            }

            $checkArray = array_filter($groupedOrders);

            if (!empty($checkArray)) {
                $visualConfig .= "[['Interveal', 'Sum'],";

                // Собираем строки для js конфигурации
                end($groupedOrders);
                $lastKey = key($groupedOrders);
                foreach ($groupedOrders as $key => $value) {
                    $visualConfig .= "['{$key}', {$value}]";
                    if ($key != $lastKey) {
                        $visualConfig .= ',';
                    }
                }
                $visualConfig .= ']';
            }
        }
        return $visualConfig;
    }
}
