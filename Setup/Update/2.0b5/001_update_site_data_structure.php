<?php
class config extends Ideal\Structure\Service\SiteData\ConfigPhp
{
    public $params = array();
}

$configSD = new config();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$sd = $configSD->params;

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
            'value' => '[[CMSLOGIN]]',
            'type' => 'Ideal_Text',
            'part' => 'cms',
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
            'part' => 'cache',
        ),
    )
);

unset($sd['default']['arr']['isTemplateCache']);
unset($sd['default']['arr']['isTemplateAdminCache']);

unset($sd['default']['arr']['templateCachePath']);

/*
$newSD = array(
    'allowResize' => array(
        'label' => 'Разрешённые размеры изображений (по одному на строку)',
        'value' => '',
        'type' => 'Ideal_Area',
        'part' => 'default',
    ),
    'startUrl' => array(
        'part' => 'cms',
    ),
    'tmpFolder' => array(
        'oldName' => 'tmpDir',
        'part' => 'cms',
    ),
    'errorLog' => array(
        'part' => 'cms',
    ),
    'adminEmail' => array(
        'label' => 'Почта, на которую будут отправляться сообщения об ошибках',
        'value' => '[[CMSLOGIN]]',
        'type' => 'Ideal_Text',
        'part' => 'cms',
    ),
    'templateSite' => array(
        'oldName' => 'isTemplateCache',
        'part' => 'cache',
    ),
    'templateAdmin' => array(
        'oldName' => 'isTemplateAdminCache',
        'part' => 'cache',
    ),
    'memcache' => array(
        'label' => 'Кэширование запросов к БД',
        'value' => '0',
        'type' => 'Ideal_Checkbox',
        'part' => 'cache',
    ),
);

$old = array('templateCachePath');
$partName = array('cms' => 'CMS', 'cache' => 'Кэширование');

foreach ($old as $k => $v) {
    if (isset($sd['default']['arr'][$v])) {
        unset($sd['default']['arr'][$v]);
    }
}

foreach ($newSD as $k => $v) {
    if (isset($v['oldName'])) {
        $name = $v['oldName'];
        unset($v['oldName']);
    } else {
        $name = $k;
    }
    if (isset($sd['default']['arr'][$name])) {
        $e = $sd['default']['arr'][$name];
        unset($sd['default']['arr'][$name]);
        $sd[$v['part']]['arr'][$k] = $e;
    } else {
        $sd[$v['part']]['arr'][$k] = $v;
        unset($sd[$v['part']]['arr'][$k]['part']);
    }
}

foreach ($sd as $k => $v) {
    if (isset($partName[$k])) {
        $sd[$k]['name'] = $partName[$k];
    }
}
*/

$configSD->params = $sd;

$configSD->saveFile($file);