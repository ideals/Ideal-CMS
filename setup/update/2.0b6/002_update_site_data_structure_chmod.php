<?php
$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$sd = $configSD->getParams();

$sd['cms']['arr']['dirMode'] = '0644';
$sd['cms']['arr']['fileMode'] = '0755';

$configSD->setParams($sd);

$configSD->saveFile($file);
