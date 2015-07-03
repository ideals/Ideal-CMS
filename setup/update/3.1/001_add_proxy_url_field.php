<?php
// 1. Добавление поля "Адрес прокси скрипта" для Яндекса в конфигурационный файл site_data.php
$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$params = $configSD->getParams();

if (!isset($params['yandex']['arr']['proxyUrl'])) {
    $params['yandex']['arr']['proxyUrl'] = array(
        'label' => 'Адрес прокси скрипта',
        'sql' => 'varchar(255)',
        'type' => 'Ideal_Text'
    );

    $configSD->setParams($params);
    $configSD->saveFile($file);
}

// 2. Добавление поля "Адрес прокси скрипта" для Яндекса в базу данных в таблицу "{prefix}ideal_addon_yandexsearch"
// Создаём подключение к БД
$dbConf = $config->db;
$db = \Ideal\Core\Db::getInstance();

$tableName = $dbConf['prefix'] . 'ideal_addon_yandexsearch';

$sql = "ALTER TABLE $tableName ADD proxyUrl varchar(255) AFTER yandexKey";
$db->query($sql);
