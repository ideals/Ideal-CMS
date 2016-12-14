<?php
/**
 * Добавление дополнительного поля в файл site_data.php
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$params = $configSD->getParams();
// Если поле уже есть, то ничего делать не нужно
if (!isset($params['cms']['arr']['indexedOptions'])) {
    $params['cms']['arr']['indexedOptions'] = array(
        'label' => 'Индексируемые параметры (по одному через запятую)',
        'value' => 'page',
        'type' => 'Ideal_Text'
    );
    $configSD->setParams($params);
    $configSD->saveFile($file);
}
