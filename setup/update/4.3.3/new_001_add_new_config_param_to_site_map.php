<?php
/**
 * Добавление дополнительных полей в файл site_data.php
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_map.php';
if ($configSD->loadFile($file)) {
    $params = $configSD->getParams();
    // Если поле уже есть, то ничего делать не нужно
    if (!isset($params['default']['arr']['is_radar'])) {
        $params['default']['arr']['is_radar'] = array(
            'label' => 'Собирать перелинковку',
            'value' => '1',
            'type' => 'Ideal_Checkbox'
        );
    }
    $configSD->setParams($params);
    $configSD->saveFile($file);
}
