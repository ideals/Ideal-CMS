<?php
$path = getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT'];
$isConsole = true;
require_once $path . '/_.php';

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

// 1. Если в site_map.php отсутствуют новые поля, то добавляем их
// Проверяем существование файла настройки карты сайта в корне
$siteMapConfigFile = $path . DIRECTORY_SEPARATOR . $config->cmsFolder . DIRECTORY_SEPARATOR . 'site_map.php';
if (file_exists($siteMapConfigFile)) {
    $configSD->loadFile($siteMapConfigFile);
    $params = $configSD->getParams();
    if (!isset($params['default']['arr']['collect_result_mail'])) {
        $params['default']['arr']['collect_result_mail'] = array (
            'label' => 'Электронная почта для уведомлений об изменениях в карте сайта (json-формат)',
            'value' => 'help@neox.ru',
            'type' => 'Ideal_Text',
            'sql' => '',
        );
    }
    if (isset($params['default']['arr']['db_host'])) {
        unset($params['default']['arr']['db_host']);
    }
    if (isset($params['default']['arr']['db_login'])) {
        unset($params['default']['arr']['db_login']);
    }
    if (isset($params['default']['arr']['db_password'])) {
        unset($params['default']['arr']['db_password']);
    }
    if (isset($params['default']['arr']['db_name'])) {
        unset($params['default']['arr']['db_name']);
    }
    if (isset($params['default']['arr']['db_prefix'])) {
        unset($params['default']['arr']['db_prefix']);
    }
    $configSD->setParams($params);
    $configSD->saveFile($siteMapConfigFile);
}
