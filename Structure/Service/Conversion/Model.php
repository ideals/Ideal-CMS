<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
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
    /** @var integer Дата с которой начинать собирать информацию */
    protected $fromTimestamp;

    /** @var integer Дата до которой нужно собрать информацию */
    protected $toTimestamp;

    /** @var mixed Строковое/числовое представление временного интервала для отображения на графике */
    protected $interval;

    /** @var integer Флаг означающий надобность отображения только новых лидов */
    protected $newLead;

    /** @var array Общий массив данных находящихся в заданном интервале  */
    protected $row;

    /** @var string Цель поиска  */
    protected $target = '';

    /** @var string Признак группировки */
    protected $group = '';

    /**
     * Инициализация модели получения данных для графика
     *
     * @param integer $fromTimestamp Дата с которой начинать собирать информацию
     * @param integer $toTimestamp Дата до которой нужно собрать информацию
     * @param mixed $interval Строковое/числовое представление временного интервала для отображения на графике
     * @param integer $newLead Флаг означающий надобность отображения только новых лидов
     */
    public function __construct($fromTimestamp, $toTimestamp, $interval = 'day', $newLead = 0)
    {
        $this->fromTimestamp = $fromTimestamp;
        $this->toTimestamp = $toTimestamp;
        $this->interval = $interval;
        $this->newLead = $newLead;
        self::getData();
    }

    /**
     * Получает конфигурационные данные для всех графиков
     *
     * @return array Массив с конфигурационными строками для графиков.
     */
    public function getOrdersInfo()
    {
        $visualConfig['quantityOfOrders'] = self::getQuantityOfOrdersInfo();
        $visualConfig['quantityOfLead'] = self::getQuantityOfLead();
        $visualConfig['referer'] = self::getRefererOrdersInfo();
        $visualConfig['sumOfOrder'] = self::getSumOfOrdersInfo();
        $visualConfig['orderType'] = self::getOrderTypeInfo();
        return $visualConfig;
    }

    /**
     * Генерирует конфигурационные данные для графика во вкладке "Общее кол-во"
     */
    protected function getQuantityOfOrdersInfo()
    {
        $visualConfig = '';

        // Запускаем процесс построения строки/js-массива
        if (count($this->row) > 0) {
            $visualConfig .= "[['Section', 'Яндекс', 'Google', 'Другие сайты', 'Прямой заход',";
            $visualConfig .= " { role: 'annotation' }],";

            // Устанавливаем цель поиска на источник перехода
            $this->target = 'referer';
            $groupedOrders = self::getGroupedOrders();

            // Разбиваем даты по реферам
            foreach ($groupedOrders as $key => $ordersInIterval) {
                // Инициализируем группирующие описания рефереров по каждой точке в интервале
                $groupedOrders[$key]['yandex'] = 0;
                $groupedOrders[$key]['google'] = 0;
                $groupedOrders[$key]['other'] = 0;
                $groupedOrders[$key]['straight'] = 0;
                if (!empty($ordersInIterval)) {
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
            }

            // Собираем строки для js конфигурации
            end($groupedOrders);
            $lastKey = key($groupedOrders);
            foreach ($groupedOrders as $key => $ordersInIterval) {
                $visualConfig .= "['{$key}', {$ordersInIterval['yandex']}, {$ordersInIterval['google']},";
                $visualConfig .= " {$ordersInIterval['other']}, {$ordersInIterval['straight']}, '']";
                if ($key != $lastKey) {
                    $visualConfig .= ',';
                }
            }
            $visualConfig .= ']';
        }
        return $visualConfig;
    }

    /**
     * Генерирует конфигурационные данные для графика во вкладке "Кол-во лидов"
     */
    protected function getQuantityOfLead()
    {
        $visualConfig = '';

        // Запускаем процесс построения строки/js-массива
        if (count($this->row) > 0) {
            $visualConfig .= "[['Section', 'Яндекс', 'Google', 'Другие сайты', 'Прямой заход',";
            $visualConfig .= " { role: 'annotation' }],";

            // Устанавливаем цель поиска на источник перехода
            $this->target = 'referer';

            // Устанавливаем группировку по контактному лицу заказчика
            $this->group = 'lead';

            $groupedOrders = self::getGroupedOrders();

            // Разбиваем даты по реферам
            foreach ($groupedOrders as $key => $ordersInIterval) {
                // Инициализируем группирующие описания рефереров по каждой точке в интервале
                $groupedOrders[$key]['yandex'] = 0;
                $groupedOrders[$key]['google'] = 0;
                $groupedOrders[$key]['other'] = 0;
                $groupedOrders[$key]['straight'] = 0;
                if (!empty($ordersInIterval)) {
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
            }

            // Собираем строки для js конфигурации
            end($groupedOrders);
            $lastKey = key($groupedOrders);
            foreach ($groupedOrders as $key => $ordersInIterval) {
                $visualConfig .= "['{$key}', {$ordersInIterval['yandex']}, {$ordersInIterval['google']},";
                $visualConfig .= " {$ordersInIterval['other']}, {$ordersInIterval['straight']}, '']";
                if ($key != $lastKey) {
                    $visualConfig .= ',';
                }
            }
            $visualConfig .= ']';
        }

        // Обнуляем группировку данных
        $this->group = 'lead';

        return $visualConfig;
    }

    /**
     * Генерирует конфигурационные данные для графика во вкладке "Источники переходов"
     */
    protected function getRefererOrdersInfo()
    {
        $visualConfig = '';

        // Запускаем процесс построения строки/js-массива
        if (count($this->row) > 0) {
            $visualConfig .= "[['Referer', 'Percentage of total'],";
            // Разбиваем заказы по реферам
            // Инициализируем группирующие описания рефереров по каждой точке в интервале
            $groupedOrders = array(
                'yandex' => array('Яндекс', 0),
                'google' => array('Google', 0),
                'other' => array('Другие сайты', 0),
                'straight' => array('Прямой заход', 0)
            );
            foreach ($this->row as $key => $value) {
                // Отлавливаем прямой переход
                if ($value['referer'] == 'null') {
                    $groupedOrders['straight'][1]++;
                } elseif (strripos($value['referer'], 'yandex') !== false) { // Отлавливаем яндекс
                    $groupedOrders['yandex'][1]++;
                } elseif (strripos($value['referer'], 'google') !== false) { // Отлавливаем гугл
                    $groupedOrders['google'][1]++;
                } else { // Отлавливаем другие сайты
                    $groupedOrders['other'][1]++;
                }
            }
            // Собираем строки для js конфигурации
            end($groupedOrders);
            $lastKey = key($groupedOrders);
            foreach ($groupedOrders as $key => $value) {
                $visualConfig .= "['{$value[0]}', {$value[1]}]";
                if ($key != $lastKey) {
                    $visualConfig .= ',';
                }
            }
            $visualConfig .= ']';
        }
        return $visualConfig;
    }

    /**
     * Генерирует конфигурационные данные для графика во вкладке "Виды заказов"
     */
    protected function getOrderTypeInfo()
    {
        $visualConfig = '';

        // Сортируем массив общих данных по типу заказа
        usort($this->row, array(__CLASS__, 'sortByOrderType'));

        if (count($this->row) > 0) {
            $visualConfig .= "[['Order type', 'Percentage of total'],";

            // Разбиваем заказы по типам
            $groupedOrders = array();
            $orderType = '';
            $orderTypeId = 0;
            foreach ($this->row as $key => $value) {
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
     * Генерирует конфигурационные данные для графика во вкладке "Сумма заказов"
     */
    protected function getSumOfOrdersInfo()
    {
        $visualConfig = '';

        // Запускаем процесс построения строки/js-массива
        if (count($this->row) > 0) {
            $this->target = 'price';
            $groupedOrders = self::getGroupedOrders();
            $visualConfig .= "[['Interveal', 'Сумма'],";

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
     * Получает данные для интервального промежутка из общего массива данных
     *
     * @param string $date Строковое представление нижней границы интервала
     * @param integer $interval Время в секундах до верхней границы интервала
     * @return array Массив данных, соответствующих заданному интевалу
     */
    protected function searchData($date, $interval)
    {
        // Переводим дату для поиска в timestamp
        $timestamp = strtotime($date);
        $resultArray = array();

        // Проходим по всему массиву данных и собираем подходящие
        foreach ($this->row as $key => $value) {
            if ($value['date_create'] >= $timestamp && $value['date_create'] <= $timestamp + $interval) {
                // Если задана группировка, то формируем данные с её учётом
                if (!$this->group) {
                    $resultArray[] = $value[$this->target];
                } elseif (!isset($resultArray[$value[$this->group]])) {
                    $resultArray[$value[$this->group]] = $value[$this->target];
                }
            }
        }
        return $resultArray;
    }

    /**
     * Получает все данные о заказах находящиеся в заданном интервале.
     * С учётом надобности отображения лидов из предыдущих периодов.
     */
    protected function getData()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $par = array('fromDate' => $this->fromTimestamp, 'toDate' => $this->toTimestamp);
        $fields = array(
            'table' => $config->db['prefix'] . 'ideal_structure_order',
            'contactPersonTable' => $config->db['prefix'] . 'ideal_structure_contactperson'
        );

        // При надобности исключаем контактные лица предыдущих периодов
        $where = '';
        if ($this->newLead) {
            $where .= ' AND e.contact_person NOT IN';
            $where .= ' (SELECT cp.ID FROM &contactPersonTable as cp WHERE cp.date_create < :fromDate';
            $where .= ' AND cp.lead IS NOT NULL AND cp.lead = (';
            $where .= ' SELECT cp2.lead FROM &contactPersonTable as cp2 WHERE ID = e.contact_person))';
        }
        $sql = 'SELECT e.*, cpjoin.lead FROM &table as e';
        $sql .= ' LEFT JOIN &contactPersonTable as cpjoin ON cpjoin.ID = e.contact_person';
        $sql .= " WHERE e.date_create >= :fromDate AND e.date_create < :toDate{$where}";
        $sql .= ' GROUP BY e.ID ORDER by e.date_create';
        $this->row = $db->select(
            $sql,
            $par,
            $fields
        );
    }

    /**
     * Генерирует массив сгруппированных данных о заказах
     *
     * @return array Массив сгруппированных данных о заказах
     */
    protected function getGroupedOrders()
    {
        $groupedOrders = array();
        $date = $this->fromTimestamp;

        // Формируем массив где ключи являются точками интевала
        while ($date <= $this->toTimestamp) {
            $tempInterval = 0;
            switch ($this->interval) {
                case 'day':
                    $tempInterval = 'day';
                    $this->interval = 86400;
                    $key = date('d.m.Y', $date);
                    break;
                case 'week':
                    // Определяем интервал до следующего понедельника
                    $tempInterval = 'week';
                    $this->interval = strtotime('next Monday', $date) - $date;

                    // Определяем конечную дату интервала для подписи
                    if ($date + $this->interval <= $this->toTimestamp) {
                        $toLabel = date('d.m.Y', $date + $this->interval - 86400);
                    } else {
                        $toLabel = date('d.m.Y', $this->toTimestamp);
                    }
                    $key = date('d.m.Y', $date) . ' - ' . $toLabel;
                    break;
                case 'month':
                    // Определяем интервал до первого числа следующего месяца
                    $tempInterval = 'month';
                    $this->interval = strtotime('first day of next month', $date) - $date;
                    $key = date('m.Y', $date);
                    break;
            }

            self::putDataToGroupedOrders($groupedOrders, $key, $date);

            $date += $this->interval;
            // Возвращаем интервалу первоналачльное значение, если оно было изменено
            if (!empty($tempInterval)) {
                $this->interval = $tempInterval;
            }
        }

        return $groupedOrders;
    }

    /**
     *  Заполняет данными элемент группировочного массива
     *
     * @param array $groupedOrders Группировочный массив
     * @param string $key Ключ элемента массива
     * @param integer $date Числовое предстовление нижней границы интервала
     */
    protected function putDataToGroupedOrders(&$groupedOrders, $key, $date)
    {
        $tempArray = self::searchData(date('d-m-Y', $date), $this->interval);
        switch ($this->target) {
            case 'referer':
                $groupedOrders[$key] = $tempArray;
                break;
            case 'price':
                $groupedOrders[$key] = 0;
                if (!empty($tempArray)) {
                    foreach ($tempArray as $price) {
                        $groupedOrders[$key] += $price;
                    }
                }
                break;
        }
    }

    /**
     * Сортирует общий массив данных по типу заказа
     * @param array $a Очередной элемент массива
     * @param array $b Следующий за очередным элемент массива
     * @return integer Сравнительный признак
     */
    protected function sortByOrderType($a, $b)
    {
        return strcmp($a['order_type'], $b['order_type']);
    }
}
