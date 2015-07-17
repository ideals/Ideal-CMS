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
conversionTemplateField($db, $tablesForConversion['structure'], $tablesForConversion['structureModified']);

// Преобразование таблиц в аддоны
conversionTemplateTables($db, $tablesForConversion['template']);

// Заменяем template.* в index.twig на addon.0.*
fixIncludedTemplates($config);

/**
 * Набор действий для преобразования таблиц структур
 *
 * @param \Ideal\Core\Db $db Объект для работы с базой даннных
 * @param array $structureTablesWithoutAddon Массив имён таблиц структур без поля 'addon'
 * @param array $structureTablesWithAddon Массив имён таблиц структур с полем 'addon'
 */
function conversionTemplateField($db, $structureTablesWithoutAddon, $structureTablesWithAddon)
{
    // Получаем значение по умолчанию для столбца 'addon'
    $defaultValue = $db->real_escape_string(json_encode(array(array('1', 'Ideal_Page', 'Текст'))));

    // Преобразуем талбицы структур у которых не было столбца 'addon'
    foreach ($structureTablesWithoutAddon as $structureTable) {
        addAddonColumn($db, $structureTable, $defaultValue);
        updateAddonColumn($db, $structureTable);
        updateTemplateColumn($db, $structureTable);
    }

    // Преобразуем талбицы структур у которых был столбец 'addon'
    foreach ($structureTablesWithAddon as $structureTable) {
        modifySettingsAddonColumn($db, $structureTable, $defaultValue);
    }
}

/**
 * Набор действий для преобразования таблиц *template* в *addon*
 *
 * @param \Ideal\Core\Db $db Объект для работы с базой даннных
 * @param array $templateTables Массив имён таблиц template в базе данных, подлежащих преобразованию
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
 * @param \Ideal\Core\Db $db Объект для работы с базой даннных
 * @param \Ideal\Core\Config $config Объект содержащий конфигурационные данне сайта
 *
 * @return array Массив имён таблиц.
 */
function getTablesForConversion($db, $config)
{
    $listTableName = array(
        'structure' => array(),
        'structureModified' => array(),
        'template' => array(),
    );

    // Получаем таблицы структур, у которых есть поле 'template' но нет поля 'addon'
    $sql = "SELECT DISTINCT TABLE_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE COLUMN_NAME = 'template'
            AND TABLE_NAME REGEXP '{$config->db['prefix']}.*structure.*'
            AND TABLE_NAME NOT IN (SELECT DISTINCT TABLE_NAME
                                   FROM INFORMATION_SCHEMA.COLUMNS
                                   WHERE COLUMN_NAME = 'addon'
                                   AND TABLE_NAME REGEXP '{$config->db['prefix']}.*structure.*'
                                   AND TABLE_SCHEMA='{$config->db['name']}')
            AND TABLE_SCHEMA='{$config->db['name']}'";
    $tablesName = $db->select($sql);
    array_walk($tablesName, function (&$v) {
        $v = $v['TABLE_NAME'];
    });
    $listTableName['structure'] += $tablesName;

    // Получаем таблицы структур, у которых есть поле 'addon'
    $sql = "SELECT DISTINCT TABLE_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE COLUMN_NAME = 'addon'
            AND TABLE_NAME REGEXP '{$config->db['prefix']}.*structure.*'
            AND TABLE_SCHEMA='{$config->db['name']}'";
    $tablesName = $db->select($sql);
    array_walk($tablesName, function (&$v) {
        $v = $v['TABLE_NAME'];
    });
    $listTableName['structureModified'] += $tablesName;

    // Получаем таблицы в именах которых присутствует 'template'
    $sql = "SELECT DISTINCT TABLE_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME REGEXP '{$config->db['prefix']}.*template.*'
            AND TABLE_SCHEMA='{$config->db['name']}'";
    $tablesName = $db->select($sql);
    array_walk($tablesName, function (&$v) {
        $v = $v['TABLE_NAME'];
    });
    $listTableName['template'] += $tablesName;
    return $listTableName;
}

/**
 * Добавляем столбец 'Addon' в таблицу '*_structure_*'
 *
 * @param \Ideal\Core\Db $db Объект для работы с базой даннных
 * @param string $tableName Название таблицы в которой производится преобразование
 * @param string $defaultValue Значение по умолчанию для столбца 'addon'
 */
function addAddonColumn($db, $tableName, $defaultValue)
{
    $sql = "ALTER TABLE $tableName ADD addon varchar(255) not null default '{$defaultValue}' AFTER template";
    $db->query($sql);
}

/**
 * Изменяем настройки столбца 'Addon' в таблице '*_structure_*'
 *
 * @param \Ideal\Core\Db $db Объект для работы с базой даннных
 * @param string $tableName Название таблицы в которой производится преобразование
 * @param string $defaultValue Значение по умолчанию для столбца 'addon'
 */
function modifySettingsAddonColumn($db, $tableName, $defaultValue)
{
    $sql = "ALTER TABLE $tableName ALTER COLUMN addon SET DEFAULT '{$defaultValue}'";
    $db->query($sql);
}

/**
 * Обновляем столбец "addon" правильными данными из столбца "template"
 *
 * @param \Ideal\Core\Db $db Объект для работы с базой даннных
 * @param string $tableName Название таблицы в которой производится преобразование
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
 * @param \Ideal\Core\Db $db Объект для работы с базой даннных
 * @param string $tableName Название таблицы в которой производится преобразование
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
 * @param \Ideal\Core\Db $db Объект для работы с базой даннных
 * @param string $templateTableName Название таблицы которую преобразуем
 * @param string $addonTableName Название таблицы в которую преобразуем
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
 * @param \Ideal\Core\Config $config Объект содержащий конфигурационные данне сайта
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
 * @param string $dir Директория для поиска
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
