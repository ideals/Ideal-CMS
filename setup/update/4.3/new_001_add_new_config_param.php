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
if (!isset($params['monitoring'])) {
    $params['monitoring'] = array(
        'name' => 'Мониторинг',
        'arr' => array(
            'scanDir' => array(
                'label' => 'Путь от корня системы до папки, в которой нужно проводить сканирование. Если пусто, то сканируется весь сайт',
                'value' => '',
                'type' => 'Ideal_Text'
            ),
            'exclude' => array(
                'label' => 'Регулярные выражения для исключения папок/файлов из сканирования',
                'value' => '',
                'type' => 'Ideal_RegexpList'
            ),
        )
    );
}
$configSD->setParams($params);
$configSD->saveFile($file);
