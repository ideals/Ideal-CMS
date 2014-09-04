<?php
$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$sd = $configSD->getParams();

if (!isset($sd['default']['arr']['allowResize'])) {
    $sd['default']['arr']['allowResize'] = array(
                'label' => 'Разрешённые размеры изображений (по одному на строку)',
                'value' => '',
                'type' => 'Ideal_Area',
                'part' => 'default',
            );
}

$sd['cms'] = array(
    'name' => 'CMS',
    'arr' => array(
        'startUrl' => $sd['default']['arr']['startUrl'],
        'tmpFolder' => $sd['default']['arr']['tmpDir'],
        'errorLog' => $sd['default']['arr']['errorLog'],
        'adminEmail' => array(
            'label' => 'Почта, на которую будут отправляться сообщения об ошибках',
            'value' => '',
            'type' => 'Ideal_Text',
        )
    )
);

unset($sd['default']['arr']['startUrl']);
unset($sd['default']['arr']['tmpDir']);
unset($sd['default']['arr']['errorLog']);
unset($sd['default']['arr']['adminEmail']);


$sd['cache'] = array(
    'name' => 'Кэширование',
    'arr' => array(
        'templateSite' => $sd['default']['arr']['isTemplateCache'],
        'templateAdmin' => $sd['default']['arr']['isTemplateAdminCache'],
        'memcache' => array(
            'label' => 'Кэширование запросов к БД',
            'value' => '0',
            'type' => 'Ideal_Checkbox',
        ),
    )
);

unset($sd['default']['arr']['isTemplateCache']);
unset($sd['default']['arr']['isTemplateAdminCache']);

unset($sd['default']['arr']['templateCachePath']);

$configSD->setParams($sd);

$configSD->saveFile($file);
