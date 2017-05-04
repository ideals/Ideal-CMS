<?php
/**
 * Добавление дополнительного поля в файл site_map.php
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
$configSD->loadFile($file);
$params = $configSD->getParams();
// Если поле уже есть, то ничего делать не нужно
if (!isset($params['yandex']['arr']['loginHint'])) {
    $params['yandex']['arr']['loginHint'] = array(
        'label' => 'Электронный адрес или имя пользователя для доступак сервису "Яндекс.Вебмастер"',
        'value' => '',
        'type' => 'Ideal_Text'
    );
}
if (!isset($params['yandex']['arr']['clientId'])) {
    $params['yandex']['arr']['clientId'] = array(
        'label' => 'Идентификатор приложения для доступа к сервису "Яндекс.Вебмастер"',
        'value' => '',
        'type' => 'Ideal_Text'
    );
}
if (!isset($params['yandex']['arr']['token'])) {
    $params['yandex']['arr']['token'] = array(
        'label' => 'Токен для авторизации в сервисе "Яндекс.Вебмастер"',
        'value' => '',
        'type' => 'Ideal_Text'
    );
}
$configSD->setParams($params);
$configSD->saveFile($file);
