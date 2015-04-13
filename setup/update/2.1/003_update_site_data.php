<?php
/**
 * Добавление дополнительных полей кэширования в конфигурационный файл site_data.php
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$params = $configSD->getParams();
$params['cache']['arr']['indexFile'] = array(
    'label' => 'Индексный файл в папке',
    'value' => 'index.html',
    'type' => 'Ideal_Text'
);
$params['cache']['arr']['fileCache'] = array(
    'label' => 'Кэширование страниц в файлы',
    'value' => '0',
    'type' => 'Ideal_Checkbox'
);
$params['cache']['arr']['excludeFileCache'] = array(
    'label' => 'Адреса для исключения из кэша (по одному на строку, формат "регулярные выражения")',
    'value' => '',
    'type' => 'Ideal_RegexpList'
);
$configSD->setParams($params);
$configSD->saveFile($file);
