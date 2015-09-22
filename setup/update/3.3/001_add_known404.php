<?php
$path = getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT'];
$isConsole = true;
require_once $path . '/_.php';

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();
$configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();
$dataList = $config->getStructureByName('Ideal_DataList');

// 1. Создаём файл хранения насроек из раздела сервиса "404 ошибки" если его ещё нет
$file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php';

if (!file_exists($file)) {
    $startContent = "<?php
// @codingStandardsIgnoreFile
return array(
);";
    file_put_contents($file, $startContent);
    $configSD->loadFile($file);
    $params = $configSD->getParams();
    $params['known'] = array(
        'name' => 'Известные 404',
        'arr' => array(
            'known404' => array(
                'label' => 'Список адресов с 404 ошибкой (по одному на строку, формат .htaccess или регулярные выражения)',
                'value' => '',
                'type' => 'Ideal_Area'
            )
        )
    );
    $params['rules'] = array(
        'name' => 'Правила игнорирования 404',
        'arr' => array(
            'rulesExclude404' => array(
                'label' => 'Набор правил для исключения из списка 404 (по одному на строку, формат .htaccess или регулярные выражени)',
                'value' => '',
                'type' => 'Ideal_Area'
            )
        )
    );
    $configSD->setParams($params);
    $configSD->saveFile($file);
    chmod($file, 0666);
}

// 2. Переносим все известные 404 в новый файл исключая повторения
$configSD->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php');
$configParams = $configSD->getParams();
if (isset($configParams['cms']['arr']['known404'])) {
    $currentKnown404 = explode("\n", $configParams['cms']['arr']['known404']['value']);
    unset($configParams['cms']['arr']['known404']);
    $configSD->setParams($configParams);
    $configSD->saveFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php');

    $configSD->loadFile($file);
    $known404Params = $configSD->getParams();
    $newKnown404 = explode("\n", $known404Params['known']['arr']['known404']['value']);
    $known404Params['known']['arr']['known404']['value'] = implode(
        "\n",
        array_unique(array_merge($newKnown404, $currentKnown404))
    );
    $configSD->setParams($known404Params);
    $configSD->saveFile($file);
}

// 3. Если в config.php отсутствует подключение Ideal_Error404 - подключаем
$error404 = $config->getStructureByName('Ideal_Error404');
if ($error404 === false) {
    $error404Id = 0;
    foreach ($config->structures as $val) {
        if ($val['ID'] > $error404Id) {
            $error404Id = $val['ID'];
        }
    }
    $error404Id++;
    $add = <<<ADD

        // Подключаем справочник 404-ых ошибок
        array(
            'ID' => {$error404Id},
            'structure' => 'Ideal_Error404',
            'name' => 'Ошибки 404',
            'isShow' => 0,
            'hasTable' => true
        ),
ADD;
    $fileName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }
    $file = file_get_contents($fileName);
    $pos = strrpos($file, ',');
    $file = substr($file, 0, $pos + 1) . $add . substr($file, $pos + 1);
    file_put_contents($fileName, $file);
    $config->loadSettings();
}

// 4. Если в таблице справочников отсутствует элемент со структурой Ideal_Error404 - создаем его
$dataListTable = $config->db['prefix'] . 'ideal_structure_datalist';
$_sql = "SELECT * FROM {$dataListTable} WHERE structure='Ideal_Error404'";
$error404 = $db->select($_sql);
if (empty($error404)) {
    $_sql = "SELECT MAX(pos) as maxPos FROM {$dataListTable}";
    $max = $db->select($_sql);
    $newPos = intval($max[0]['maxPos']) + 1;

    // Создаем запись Заказы с сайта в Справочниках
    $db->insert(
        $dataListTable,
        array(
            'prev_structure' => "0-{$dataList['ID']}",
            'structure' => 'Ideal_Error404',
            'pos' => $newPos,
            'name' => 'Ошибки 404',
            'url' => 'oshibki-404',
            'parent_url' => '---',
            'annot' => ''
        )
    );
}

// 5. Если отсутствует таблица для Ideal_Error404 - создаем её
$cfg = $config->getStructureByName('Ideal_Error404');
$table = $config->db['prefix'] . 'ideal_structure_error404';
$sql = "SHOW TABLES LIKE '{$table}'";
$res = $db->select($sql);
if (empty($res)) {
    // Создание таблицы для справочника
    $db->create($table, $cfg['fields']);
}

// 6. Если в настройках в разделе CMS отсутствует галка "Уведомление о 404ых ошибках", то добавляем её туда
$configSD->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php');
$configParams = $configSD->getParams();
if (!isset($configParams['cms']['arr']['error404Notice'])) {
    $configParams['cms']['arr']['error404Notice'] =
        array(
            'label' => 'Уведомление о 404ых ошибках',
            'value' => '0',
            'type' => 'Ideal_Checkbox'
        );
    $configSD->setParams($configParams);
    $configSD->saveFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php');
}
