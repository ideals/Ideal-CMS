<?php
$path = getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT'];
$isConsole = true;
require_once $path . '/_.php';

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

// 1. Если в site_map.php отсутствуют новые поля, то добавляем их
// Проверяем существование файла настройки карты сайта в корне
$siteMapConfigFile = $path . DIRECTORY_SEPARATOR . $cmsFolder . DIRECTORY_SEPARATOR . 'site_map.php';
if (file_exists($siteMapConfigFile)) {
    $configSD->loadFile($siteMapConfigFile);
    $params = $configSD->getParams();
    if (!isset($params['default']['arr']['existence_time_file'])) {
        $params['default']['arr']['existence_time_file'] = array (
            'label' => 'Максимальное время существования версии промежуточного файла (часы)',
            'value' => '25',
            'type' => 'Ideal_Text',
            'sql' => '',
        );
    }
    if (!isset($params['default']['arr']['db_host'])) {
        $params['default']['arr']['db_host'] = array (
            'label' => 'Хост для подключения к базе данных',
            'value' => 'localhost',
            'type' => 'Ideal_Text',
            'sql' => '',
        );
    }
    if (!isset($params['default']['arr']['db_login'])) {
        $params['default']['arr']['db_login'] = array (
            'label' => 'Логин для подключения к базе данных',
            'value' => '',
            'type' => 'Ideal_Text',
            'sql' => '',
        );
    }
    if (!isset($params['default']['arr']['db_password'])) {
        $params['default']['arr']['db_password'] = array (
            'label' => 'Пароль для подключения к базе данных',
            'value' => '',
            'type' => 'Ideal_Text',
            'sql' => '',
        );
    }
    if (!isset($params['default']['arr']['db_name'])) {
        $params['default']['arr']['db_name'] = array (
            'label' => 'Название базы данных',
            'value' => '',
            'type' => 'Ideal_Text',
            'sql' => '',
        );
    }
    if (!isset($params['default']['arr']['db_prefix'])) {
        $params['default']['arr']['db_prefix'] = array (
            'label' => 'Префикс базы данных',
            'value' => 'i_',
            'type' => 'Ideal_Text',
            'sql' => '',
        );
    }
    $configSD->setParams($params);
    $configSD->saveFile($siteMapConfigFile);
}
