<?php
/**
 * Создание настройки $config->cache['jsAndCss'] в site_data.php для объединения и минимизации JS и CSS
 */
$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$sd = $configSD->getParams();

if (!isset($sd['cache']['arr']['jsAndCss'])) {
    $arr = array_reverse($sd['cache']['arr'], true);
    $arr['jsAndCss'] = array(
                'label' => 'Объединение и минификация css и js файлов',
                'value' => '0',
                'type' => 'Ideal_Checkbox'
    );
    $sd['cache']['arr'] = array_reverse($arr, true);
}

$configSD->setParams($sd);

$configSD->saveFile($file);
