<?php
/**
 * Добавление дополнительных полей для настроек SMTP в конфигурационный файл site_data.php
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$params = $configSD->getParams();
// Если поле уже есть, то ничего делать не нужно
if (!isset($params['smtp'])) {
    $params['smtp'] = array(
        'name' => 'SMTP',
        'arr' => array(
            'server' => array(
                'label' => 'Адрес SMTP-сервера',
                'value' => '',
                'type' => 'Ideal_Text'
            ),
            'port' => array(
                'label' => 'Порт SMTP-сервера',
                'value' => '',
                'type' => 'Ideal_Text'
            ),
            'user' => array(
                'label' => 'Имя пользователя для авторизации на SMTP-сервере',
                'value' => '',
                'type' => 'Ideal_Text'
            ),
            'password' => array(
                'label' => 'Пароль для авторизации на SMTP-сервере',
                'value' => '',
                'type' => 'Ideal_Text'
            ),
            'domain' => array(
                'label' => 'Домен, с которого идёт отправка письма',
                'value' => '',
                'type' => 'Ideal_Text'
            )
        )
    );
}
$configSD->setParams($params);
$configSD->saveFile($file);
