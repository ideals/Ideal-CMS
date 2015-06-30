<?php

namespace check;

// Абсолютный адрес размещения админки
define(
    'CMS_ROOT',
    $_SERVER['DOCUMENT_ROOT'] . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '/Ideal/setup'))
);

define('SITE', $_SERVER['DOCUMENT_ROOT']);

class CheckInstallation
{
    /**
     * Подключение папок админки, инициализация автозагрузчика классов
     * Запуск метода run()
     */
    public function __construct()
    {
        // В пути поиска по умолчанию включаем корень сайта, путь к Ideal и папке кастомизации CMS
        set_include_path(
            get_include_path()
            . PATH_SEPARATOR . CMS_ROOT
            . PATH_SEPARATOR . CMS_ROOT . '/Ideal.c/'
            . PATH_SEPARATOR . CMS_ROOT . '/Ideal/'
            . PATH_SEPARATOR . CMS_ROOT . '/Mods/'
        );

        // Подключаем автозагрузчик классов
        require_once 'Core/AutoLoader.php';

        $this->run();
    }

    /**
     * Вызов поочередно всех функций проверки конфига и таблиц в БД
     */
    public function run()
    {
        $this->checkConfig();
        $this->checkTable();
        $this->createOrder();
    }

    /*
     * Метод проверки наличия справочников в конфиге
     * Добавляет справочники в конфиг, если их там нет
     */
    private function checkConfig()
    {
        $config = \Ideal\Core\Config::getInstance();
        $directory = $config->getStructureByName('Ideal_DataList');

        if ($directory === false) {
            $ID = count($config->structures) + 1;
            $add =<<<ADD

        // Подключаем справочники
        array(
            'ID' => {$ID},
            'structure' => 'Ideal_DataList',
            'name' => 'Справочники',
            'isShow' => 1,
            'hasTable' => true
        ),
ADD;
            $fileName = CMS_ROOT . '/config.php';
            $file = file_get_contents($fileName);

            $pos = strrpos($file, ',');

            $file = substr($file, 0, $pos + 1) . $add . substr($file, $pos + 1);

            file_put_contents($fileName, $file);

            unset($config);
        }
    }

    /**
     * Проверка наличия в БД таблицы для справочников
     * Если таблицы нет, то она будет создана
     */
    private function checkTable()
    {
        // Получаем конфигурационные данные сайта
        $config = \Ideal\Core\Config::getInstance();

        // Создаём подключение к БД
        $dbConf = $config->db;
        $db = \Ideal\Core\Db::getInstance();

        $table = $dbConf['prefix'] . 'ideal_structure_datalist';

        $res = $db->select("SHOW TABLES LIKE '{$table}'");

        // Если таблицы ideal_structure_datalist не существует - создаем её
        if ($res == null) {
            // Дописать считывание Structure/Datalist/config.php и создание таблицы
            $filename = CMS_ROOT . '/Ideal/Structure/DataList/config.php';
            $file = require($filename);
            $db->create($table, $file['fields']);

        }
    }

    /**
     * Создание записи о заказах в таблице Спраочников, если таковой нет
     * Создание таблицы для заказов, если таковой нет
     */
    private function createOrder()
    {
        // Получаем конфигурационные данные сайта
        $config = \Ideal\Core\Config::getInstance();

        // Создаём подключение к БД
        $db = \Ideal\Core\Db::getInstance();

        $cfg = $config->getStructureByName('Ideal_Order');

        $dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';

        $_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
        $max = $db->select($_sql);
        $_sql = "SELECT * FROM {$dataListTable} WHERE structure='Ideal_Order'";
        $order = $db->select($_sql);

        // Создаем запись Заказы с сайта в Справочниках
        if ($max[0]['maxPos'] == null || $order == null) {
            $newPos = intval($max[0]['maxPos']) + 1;
            $conf = $config->getStructureByName('Ideal_DataList');
            $db->insert(
                $dataListTable,
                array(
                    'prev_structure' => "0-{$conf['ID']}",
                    'structure' => 'Ideal_Order',
                    'pos' => $newPos,
                    'name' => 'Заказы с сайта',
                    'url' => 'zakazy-s-sajta',
                    'parent_url' => '---',
                    'annot' => ''
                )
            );
        }
        // Создаем таблицу заказов если её нет
        $table = $config->db['prefix'] . 'ideal_structure_order';
        $sql = "SHOW TABLES LIKE '{$table}'";
        $res = $db->select($sql);
        if ($res == null) {
            // Создание таблицы для справочника
            $db->create($table, $cfg['fields']);
        }
    }
}

$isConsole = true;
require_once(SITE . '/_.php');

$A = new CheckInstallation();
