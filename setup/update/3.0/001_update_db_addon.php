<?php
/**
 * Преобразования базы данных, связанные с переходом от шаблонов к аддонам.
 */

$path = getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT'];
$isConsole = true;
require_once $path . '/_.php';

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();

// Создаём подключение к БД
$dbConf = $config->db;
$db = new \Ideal\Core\Db($dbConf['host'], $dbConf['login'], $dbConf['password'], $dbConf['name']);
$db::getInstance();
// Получение списка таблиц для дальнейшего преобразования
$tablesForConversion = getTablesForConversion($db, $config);

// Преобразование полей в структурах
conversionTemplateField($db, $tablesForConversion['structure']);

// Преобразование таблиц в аддоны
conversionTemplateTables($db, $tablesForConversion['template']);

// Заменяем template.* в index.twig на addon.0.*
fixIncludedTemplates($config);

/**
 * Набор действий для преобразования таблиц структур
 *
 * @param $db \Ideal\Core\Db для работы с базой даннных
 * @param $structureTables Array имён таблиц в базе данных, хранящих информацию о структурах, подлежащих преобразованию
 */
function conversionTemplateField($db, $structureTables)
{
    foreach ($structureTables as $structureTable) {
        addAddonColumn($db, $structureTable);
        updateAddonColumn($db, $structureTable);
        updateTemplateColumn($db, $structureTable);

    }
}

/**
 * Набор действий для преобразования таблиц *template* в *addon*
 *
 * @param $db \Ideal\Core\Db для работы с базой даннных
 * @param $templateTables array имён таблиц template в базе данных, подлежащих преобразованию
 */
function conversionTemplateTables($db, $templateTables)
{
    foreach ($templateTables as $templateTable) {
        conversionTemplateTable($db, $templateTable, str_replace('template', 'addon', $templateTable));
    }
}


/**
 * Получаем список таблиц для преобразования
 *
 * @param $db \Ideal\Core\Db для работы с базой даннных
 * @param $config \Ideal\Core\Config с конфигурационными данными сайта
 *
 * @return array Массив имён таблиц.
 */
function getTablesForConversion($db, $config)
{
    $listTableName = array(
        'template' => array(),
        'structure' => array(),
    );
    $sql = "SHOW TABLES";
    $tablesName = $db->query($sql);
    $listTableName = array();
    while ($tableName = $tablesName->fetch_array()) {
        $pattern = '/' . $config->db['prefix'] . '.*_structure.*?/i';
        if (preg_match($pattern, $tableName[0])) {
            $listTableName['structure'][] = $tableName[0];
        }

        $pattern = '/' . $config->db['prefix'] . '.*_template.*?/i';
        if (preg_match($pattern, $tableName[0])) {
            $listTableName['template'][] = $tableName[0];
        }
    }

    // Выбираем только те таблицы структур у которых есть поле "template"
    foreach ($listTableName['structure'] as $key => $structureTableName) {
        $tableFields = array();
        $sql = "SHOW COLUMNS FROM $structureTableName";
        $tableFieldsRes = $db->query($sql);
        while ($tableFieldsRow = $tableFieldsRes->fetch_array(MYSQLI_ASSOC)) {
            $tableFields[] = $tableFieldsRow['Field'];
        }
        if (array_search('template', $tableFields) === false || array_search('addon', $tableFields) === true) {
            unset($listTableName['structure'][$key]);
        }
    }
    return $listTableName;
}

/**
 * Добавляем столбец 'Addon' в таблицу '*_structure_*'
 *
 * @param $db \Ideal\Core\Db для работы с базой даннных
 * @param $tableName string Название таблицы в которой производится преобразование
 */
function addAddonColumn($db, $tableName)
{
    // Получаем значение по умолчанию для столбца 'addon'
    $defaultValue = $db->real_escape_string(json_encode(array(array('1', 'Ideal_Page', 'Текст'))));

    $sql = "SHOW COLUMNS FROM $tableName WHERE Field = 'addon'";
    $res = $db->select($sql);
    if (empty($res)) {
        $sql = "ALTER TABLE $tableName ADD addon varchar(255) not null default '{$defaultValue}' AFTER template";
    } else {
        $sql = "ALTER TABLE $tableName ALTER COLUMN addon SET DEFAULT '{$defaultValue}'";
    }
    $db->query($sql);
}

/**
 * Обновляем столбец "addon" правильными данными из столбца "template"
 *
 * @param $db \Ideal\Core\Db для работы с базой даннных
 * @param $tableName string Название таблицы в которой производится преобразование
 */
function updateAddonColumn($db, $tableName)
{
    $rows = $db->select("SELECT ID, template FROM $tableName");
    foreach ($rows as $value) {
        $value['addon'] = json_encode(array(array('1', $value['template'])));
        $params = array('ID' => $value['ID']);
        unset($value['template']);
        unset($value['ID']);
        $db->update($tableName)->set($value)->where('ID = :ID', $params)->exec();
    }
}

/**
 * Пересоздаём столбец "template" с правильными данными
 *
 * @param $db \Ideal\Core\Db для работы с базой даннных
 * @param $tableName string Название таблицы в которой производится преобразование
 */
function updateTemplateColumn($db, $tableName)
{
    $sql = "ALTER TABLE $tableName DROP template";
    $db->query($sql);
    $sql = "ALTER TABLE $tableName ADD template varchar(255) default 'index.twig' AFTER structure";
    $db->query($sql);
}

/**
 * Преобразуем таблицу "*_template_*" в "*_addon_*".
 *
 * @param $db \Ideal\Core\Db для работы с базой даннных
 * @param $templateTableName string Название таблицы которую преобразуем
 * @param $addonTableName string Название таблицы в которую преобразуем
 */
function conversionTemplateTable($db, $templateTableName, $addonTableName)
{
    $sql = "RENAME TABLE `$templateTableName` TO `$addonTableName`";
    $db->query($sql);
    $sql = "ALTER TABLE $addonTableName ADD tab_ID int not null default 1 AFTER prev_structure";
    $db->query($sql);
}

/**
 * Заменяем содержимое шаблона template на addons.0
 *
 * @param $config \Ideal\Core\Config
 */
function fixIncludedTemplates($config)
{
    $files = findTwigTemplates(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal.c');
    foreach ($files as $file) {
        $src = file_get_contents(realpath($file));
        $src = str_replace('template.', 'addons.0.', $src);
        file_put_contents(realpath($file), $src);
    }
}

/**
 * Рекурсивный сбор файлов twig
 *
 * @param $dir string Директория для поиска
 * @return array Массив путей к файлам
 */
function findTwigTemplates($dir)
{
    $files = array();
    if ($handle = opendir($dir)) {
        while (false !== ($item = readdir($handle))) {
            if (is_file("$dir/$item") && strpos($item, '.twig') !== false) {
                $files[] = "$dir/$item";
            } elseif (is_dir("$dir/$item") && ($item != ".") && ($item != "..")) {
                $files = array_merge($files, findTwigTemplates("$dir/$item"));
            }
        }
        closedir($handle);
    }
    return $files;
}
