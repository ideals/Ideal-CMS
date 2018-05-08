<?php
/**
 * Добавление дополнительных полей в файл site_data.php.
 * И добавление строк в файл .htaccess для обеспечения управления наличием настроек браузерного кэширования
 */

$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();

// Добавляем параметр в настройки кэширования
$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
if ($configSD->loadFile($file)) {
    $params = $configSD->getParams();
    // Если поле уже есть, то ничего делать не нужно
    if (!isset($params['cache']['arr']['browserCache'])) {
        $params['cache']['arr']['browserCache'] = array(
            'label' => 'Кэширование в браузере',
            'value' => '0',
            'type' => 'Ideal_Checkbox'
        );
    }
    $configSD->setParams($params);
    $configSD->saveFile($file);
}

// Добавляем строки в .htaccess
$filePath = DOCUMENT_ROOT . '/.htaccess';
$fileContent = file_get_contents($filePath);
if (!preg_match('/(# browser cache)(.*)(# end browser cache)/isU', $fileContent)) {
    $fileContent .= <<<string
    
# browser cache
# end browser cache
string;
    file_put_contents($filePath, $fileContent);
}
