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
if (!isset($params['cron']['arr']['crontab'])) {
    $label = 'Формат ввода данных аналогичен cron-у в системе, кроме надобноости указания исполнителя.';
    $label .= '<br \> Пример: "* * * * * /path/to/script.php" (без кавычек)';
    $params['cron'] = array(
        'name' => 'Cron',
        'arr' => array(
            'crontab' => array(
                'label' => $label,
                'value' => '',
                'type' => 'Ideal_Area'
            ))
    );
}
$configSD->setParams($params);
$configSD->saveFile($file);
