<?php
/**
 * Добавление дополнительного поля в файл site_map.php
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_map.php';
$configSD->loadFile($file);
$params = $configSD->getParams();
// Если поле уже есть, то ничего делать не нужно
if (!isset($params['default']['arr']['tmp_radar_file'])) {
    $params['default']['arr']['tmp_radar_file'] = array(
        'label' => 'Путь от корня сайта к временному файлу отчёта о перелинковке',
        'value' => '/tmp/radar.part',
        'type' => 'Ideal_Text'
    );
    $configSD->setParams($params);
    $configSD->saveFile($file);
}
