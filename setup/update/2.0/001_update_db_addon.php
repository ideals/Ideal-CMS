<?php
/**
 * Преобразования базы данных, связанные с переходом от шаблонов к аддонам.
 */

// Получаем конфигурационные данные сайта
$config = \Ideal\Core\Config::getInstance();

// Создаём подключение к БД
$db = new Ideal\Core\Db($config['db']['host'], $config['db']['login'], $config['db']['password'], $config['db']['name']);

// Получение списка таблиц для дальнейшего преобразования
$tablesForConversion = getTablesForConversion($db, $config);

// Преобразование полей в структурах
conversionTemplateField($db, $tablesForConversion['structure']);

// Преобразование таблиц в аддоны
conversionTemplateTables($db, $tablesForConversion['template']);

/**
 * Набор действий для преобразования таблиц структур
 *
 * @param $db Объект для работы с базой даннных
 * @param $structureTables Массив имён таблиц в базе данных, хранящих информацию о структурах, подлежащих преобразованию
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
 * @param $db Объект для работы с базой даннных
 * @param $templateTables Массив имён таблиц template в базе данных, подлежащих преобразованию
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
 * @param $db Объект для работы с базой даннных
 * @param $config Массив с конфигурационными данными сайта
 *
 * @return array Массив имён таблиц.
 */
function getTablesForConversion($db, $config)
{
    $sql = "SHOW TABLES";
    $tablesName = $db->query($sql);
    while ($tableName = $tablesName->fetch_array()) {
        $pattern = '/' . $config['db']['prefix'] . '.*_structure.*?/i';
        if (preg_match($pattern, $tableName[0])) {
            $listTableName['structure'][] = $tableName[0];
        }

        $pattern = '/' . $config['db']['prefix'] . '.*_template.*?/i';
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
        if (array_search('template', $tableFields) === false) {
            unset($listTableName['structure'][$key]);
        }
    }
    return $listTableName;
}

/**
 * Добавляем столбец 'Addon' в таблицу '*_structure_*'
 *
 * @param $db Объект для работы с базой даннных
 * @param string $tableName Название таблицы в которой производится преобразование
 */
function addAddonColumn($db, $tableName)
{
    $sql = "ALTER TABLE $tableName ADD addon varchar(255) not null default '{\"1\":\"Ideal_Page\"}' AFTER template";
    $db->query($sql);
}

/**
 * Обновляем столбец "addon" правильными данными из столбца "template"
 *
 * @param $db Объект для работы с базой даннных
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
 * @param $db Объект для работы с базой даннных
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
 * @param $db Объект для работы с базой даннных
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
