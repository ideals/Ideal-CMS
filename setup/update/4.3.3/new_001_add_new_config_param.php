<?php
/**
 * Добавление дополнительных полей в файл site_data.php
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$params = $configSD->getParams();
// Если поле уже есть, то ничего делать не нужно
if (!isset($params['crm'])) {
    $params['crm'] = array(
        'name' => 'CRM',
        'arr' => array(
            'email' => array(
                'label' => 'E-mail адрес для сканирования',
                'value' => '',
                'type' => 'Ideal_Text'
            ),
            'password' => array(
                'label' => 'Пароль для доступа к e-mail',
                'value' => '',
                'type' => 'Ideal_Text'
            ),
        )
    );
}
$configSD->setParams($params);
$configSD->saveFile($file);
