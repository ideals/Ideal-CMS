<?php
/**
 * Добавление дополнительных полей для Яндекса в конфигурационный файл site_data.php
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$params = $configSD->getParams();
$params['yandex'] = array(
    'name' => 'Яндекс',
    'arr' => array(
        'yandexLogin' => array(
            'label' => 'Яндекс логин',
            'value' => '',
            'type' => 'Ideal_Text'
        ),
        'yandexKey' => array(
            'label' => 'Яндекс ключ',
            'value' => '',
            'type' => 'Ideal_Text'
        )
    )
);
$configSD->setParams($params);
$configSD->saveFile($file);
