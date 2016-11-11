<?php
$path = getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT'];
$isConsole = true;
require_once $path . '/_.php';

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

// 1. Проверяем и при надобности заменяем в site_map.php поле 'collect_result_mail' на 'email_json'
// Проверяем существование файла настройки карты сайта в корне
$siteMapConfigFile = $path . DIRECTORY_SEPARATOR . $config->cmsFolder . DIRECTORY_SEPARATOR . 'site_map.php';
if (file_exists($siteMapConfigFile)) {
    $configSD->loadFile($siteMapConfigFile);
    $params = $configSD->getParams();
    if (isset($params['default']['arr']['collect_result_mail']) && !isset($params['default']['arr']['email_json'])) {
        $params['default']['arr']['email_json'] = array(
            'label' => 'Электронная почта для уведомлений об изменениях в карте сайта (json-формат)',
            'value' => $params['default']['arr']['collect_result_mail']['value'],
            'type' => 'Ideal_Text',
            'sql' => '',
        );
        unset($params['default']['arr']['collect_result_mail']);
    }
    $configSD->setParams($params);
    $configSD->saveFile($siteMapConfigFile);
}
