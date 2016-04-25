<?php
namespace Ideal\Structure\Service\CheckDb;

?>

<ul class="nav nav-tabs">

    <li class="active"><a href="#bd" data-toggle="tab">База данных</a></li>
    <li><a href="#cache" data-toggle="tab">Кэш</a></li>
    <li><a href="#cmsFiles" data-toggle="tab">Файлы CMS</a></li>

</ul>

<div class="tab-content">

    <div class="tab-pane well active" id="bd">

        <form method="POST" action="">

<?php
use Ideal\Core\Config;
use Ideal\Core\Db;

$db = Db::getInstance();
$config = Config::getInstance();

$result = $db->select('SHOW TABLES');

$dbTables = array();
foreach ($result as $v) {
    $table = array_shift($v);

    // Получаем информацию о полях таблицы
    $fieldsInfo = $db->select('SHOW COLUMNS FROM ' . $table . ' FROM `' . $config->db['name'] . '`');
    $fields = array();
    array_walk($fieldsInfo, function ($v) use (&$fields) {
        $key = $v['Field'];
        unset($v['Field']);
        list($type) = explode(' ', implode(' ', $v));
        $fields[$key] = $type;
    });
    if (strpos($table, $config->db['prefix']) === 0) {
        $dbTables[$table] = $fields;
    }
}

$checkTypeFile = function ($dir, $module, &$cfgTables, &$cfgTablesFull, &$config, $type) {
    if (!($handle = opendir($dir))) {
        // Невозможно открыть папку, значит ничего делать не надо
        return;
    }
    while (false !== ($file = readdir($handle))) {
        if (($file != '.') && ($file != '..') && (is_dir($dir . '/' . $file))) {
            /** @noinspection PhpIncludeInspection */
            $c = require($dir . '/' . $file . '/config.php');
            if (isset($c['params']['has_table']) && ($c['params']['has_table'] == false)) {
                continue;
            }
            $t = strtolower($config->db['prefix'] . $module . '_' . $type . '_' . $file);
            if (array_key_exists($t, $cfgTables) === false) {
                $fields = getFieldListWithTypes($c);
                $cfgTables[$t] = $fields;
                $cfgTablesFull[$t] = ($module == 'Ideal') ? $type . '/' . $file : $module . '/' . $type . '/' . $file;
            }
        }
    }
};

$cfgTables = array();
$cfgTablesFull = array();
foreach ($config->structures as $v) {
    if (!$v['hasTable']) {
        continue;
    }
    $fields = getFieldListWithTypes($v);
    list($module, $structure) = explode('_', $v['structure'], 2);
    $table = strtolower($config->db['prefix'] . $module . '_structure_' . $structure);
    $cfgTables[$table] = $fields;

    // Обработка папки с кастомными аддонами
    $dir = ($module == 'Ideal') ? $config->cmsFolder . '/Ideal.c' : $config->cmsFolder . '/' . 'Mods.c/' . $module;
    $dir = stream_resolve_include_path($dir . '/Addon');
    $checkTypeFile($dir, $module, $cfgTables, $cfgTablesFull, $config, 'Addon');
    // Обработка папки с аддонами
    $dir = ($module == 'Ideal') ? $config->cmsFolder . '/' : $config->cmsFolder . '/' . 'Mods/';
    $dir = stream_resolve_include_path($dir . $module . '/Addon');
    $checkTypeFile($dir, $module, $cfgTables, $cfgTablesFull, $config, 'Addon');

    // Обработка папки с кастомными связующими таблицами
    $dir = ($module == 'Ideal') ? $config->cmsFolder . '/Ideal.c/' : $config->cmsFolder . '/' . 'Mods.c/';
    $dir = stream_resolve_include_path($dir . $module . '/Medium');
    $checkTypeFile($dir, $module, $cfgTables, $cfgTablesFull, $config, 'Medium');
    // Обработка папки с связующими таблицами
    $dir = ($module == 'Ideal') ? $config->cmsFolder . '/' : $config->cmsFolder . '/' . 'Mods/';
    $dir = stream_resolve_include_path($dir . $module . '/Medium');
    $checkTypeFile($dir, $module, $cfgTables, $cfgTablesFull, $config, 'Medium');

    $module = ($module == 'Ideal') ? '' : $module . '/';
    $cfgTablesFull[$table] = $module . 'Structure/' . $structure;
}

// Если есть таблицы, которые надо создать
if (isset($_POST['create'])) {
    foreach ($_POST['create'] as $table => $v) {
        echo '<p>Создаём таблицу ' . $table . '…';
        $file = $cfgTablesFull[$table] . '/config.php';
        /** @noinspection PhpIncludeInspection */
        $data = include($file);
        $fields = getFieldListWithTypes($data);
        $db->create($table, $data['fields']);
        echo ' Готово.</p>';
        $dbTables[$table] = $fields;
    }
}

// Если есть поля, которые надо создать
if (isset($_POST['create_field'])) {
    foreach ($_POST['create_field'] as $tableField => $v) {
        list($table, $field) = explode('-', $tableField);
        echo '<p>Добавляем поле ' . $field . ' в таблицу ' . $table . '…';
        $file = $cfgTablesFull[$table] . '/config.php';
        /** @noinspection PhpIncludeInspection */
        $data = include($file);

        //Поиск поля после которого нужно вставить новое
        $afterThisField = '';
        foreach ($data['fields'] as $key => $value) {
            $value['sql'] = trim($value['sql']);
            if ($key != $field && !empty($value['sql'])) {
                $afterThisField = $key;
            } else {
                break;
            }
        }

        if (!empty($afterThisField)) {
            $afterThisField = ' AFTER ' . $afterThisField;
        } else {
            $afterThisField = ' FIRST';
        }

        // Составляем sql запрос для вставки поля в таблицу
        $sql = "ALTER TABLE {$table} ADD {$field} {$data['fields'][$field]['sql']}"
            . " COMMENT '{$data['fields'][$field]['label']}' {$afterThisField};";
        $db->query($sql);
        echo ' Готово.</p>';
        $fields = getFieldListWithTypes($data);
        $dbTables[$table][$field] = $fields[$field];
    }
}

// Если есть таблицы, которые надо удалить
if (isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $table => $v) {
        echo '<p>Удаляем таблицу ' . $table . '…';
        $db->query("DROP TABLE `{$table}`");
        echo ' Готово.</p>';
        unset($dbTables[$table]);
    }
}

// Если есть поля, которые нужно удалить
if (isset($_POST['delete_field'])) {
    foreach ($_POST['delete_field'] as $tableField => $v) {
        list($table, $field) = explode('-', $tableField);
        echo '<p>Удаляем поле ' . $field . ' в таблице ' . $table . '…';
        $db->query("ALTER TABLE {$table} DROP COLUMN {$field};");
        echo ' Готово.</p>';
        unset($dbTables[$table][$field]);
    }
}

// Если есть поля, которые нужно преобразовать
if (isset($_POST['change_type'])) {
    foreach ($_POST['change_type'] as $tableField => $v) {
        list($table, $field, $type) = explode('-', $tableField);
        echo '<p>Изменяем поле ' . $field . ' в таблице ' . $table . ' на тип' . $type . '…';
        $db->query("ALTER TABLE {$table} MODIFY {$field} {$type};");
        echo ' Готово.</p>';
        $dbTables[$table][$field] = $type;
    }
}


$isCool = true;

foreach ($cfgTables as $tableName => $tableFields) {
    if (!array_key_exists($tableName, $dbTables)) {
        echo '<p class="well"><input type="checkbox" name="create[' . $tableName . ']">&nbsp; ';
        echo 'Таблица <strong>' . $tableName . '</strong> отсутствует в базе данных. Создать?</p>';
        $isCool = false;
    } else {
        // Получаем массив полей, которые нужно предложить создать
        $onlyConfigExist = array_diff_key($tableFields, $dbTables[$tableName]);

        // Предлагать создавать нужно только те поля, у которых определён sql тип.
        $onlyConfigExist = array_filter($onlyConfigExist);

        // Если какое-либо поле присутствует только в конфигурационном файле, то предлагаем его создать
        if (count($onlyConfigExist) > 0) {
            foreach ($onlyConfigExist as $missingField => $missingFieldType) {
                echo '<p class="well">';
                echo '<input type="checkbox" name="create_field[' . $tableName . '-' . $missingField . ']">&nbsp; ';
                echo 'В таблице <strong>' . $tableName . '</strong> ';
                echo 'отсутствует поле <strong>' . $missingField . '</strong>. Создать?</p>';
            }
            $isCool = false;
        }

        // Получаем массив полей, которые нужно предложить удалить
        $onlyBaseExist = array_diff_key($dbTables[$tableName], $tableFields);

        // Если какое-либо поле присутствует только в базе данных, то предлагаем его удалить
        if (count($onlyBaseExist) > 0) {
            foreach ($onlyBaseExist as $excessField => $excessFieldType) {
                echo '<p class="well">';
                echo '<input type="checkbox" name="delete_field[' . $tableName . '-' . $excessField . ']">&nbsp; ';
                echo 'Поле <strong>' . $excessField . '</strong> ';
                echo 'отсутствует в конфигурации таблицы <strong>' . $tableName . '</strong>. Удалить?</p>';
            }
            $isCool = false;
        }

        $fieldTypeDiff = diffConfigBaseType($tableFields, $dbTables[$tableName]);
        // Если есть расхождение в типах полей, то предлагаем вернуть всё к виду конфигурационных файлов
        if (count($fieldTypeDiff) > 0) {
            foreach ($fieldTypeDiff as $fieldName => $typeDiff) {
                echo '<p class="well">';
                echo '<input type="checkbox" ';
                echo 'name="change_type[' . $tableName . '-' . $fieldName . '-' . $typeDiff['conf'] . ']">&nbsp; ';
                echo 'Поле <strong>' . $fieldName . '</strong> в таблице <strong>' . $tableName . ' </strong> ';
                echo 'имеет тип <strong>' . $typeDiff['base'] . '</strong>, ';
                echo 'но в конфигурационном файле это поле определено типом ';
                echo '<strong>' . $typeDiff['conf'] . '</strong>. Преобразовать поле в базе данных?</p>';
            }
            $isCool = false;
        }

        // Удаляем имеющиеся в конфигурации таблицы из списка таблиц в базе
        unset($dbTables[$tableName]);
    }
}

foreach ($dbTables as $tableName => $tableFields) {
    echo '<p class="well"><input type="checkbox" name="delete[' . $tableName . ']">&nbsp;';
    echo 'Таблица <strong>' . $tableName . '</strong> отсутствует в конфигурации. Удалить?</p>';
    $isCool = false;
}

// После нажатия на кнопку применить и совершения действий, нужно либо заново перечитывать БД, либо перегружать страницу
if (isset($_POST['create']) || isset($_POST['delete']) || isset($_POST['create_field'])
    || isset($_POST['delete_field']) || isset($_POST['change_type'])) {
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($isCool) {
    echo 'Конфигурация в файлах соответствует конфигурации базы данных.';
} else {
    echo '<button class="btn btn-primary btn-large" type="submit">Применить</button>';
}

// Получаем информацию о полях из конфигурационных файлов
function getFieldListWithTypes($data)
{
    $fields = array();
    if (isset($data['fields']) && is_array($data['fields'])) {
        array_walk($data['fields'], function ($value, $key) use (&$fields) {
            if (isset($value['sql'])) {
                list($type) = explode(' ', $value['sql']);
                $fields[$key] = $type;
            }
        });
    }
    return $fields;
}

function diffConfigBaseType($a, $b)
{
    $result = array();
    foreach ($a as $k => $v) {
        if (isset($b[$k])) {
            if ($v === 'bool') {
                $v = 'tinyint(1)';
            }
            if (!preg_match('/^' . quotemeta($v) . '(.*?)/', $b[$k])) {
                $result[$k]['conf'] = $v;
                $result[$k]['base'] = $b[$k];
            }
        }
    }
    return $result;
}
?>

</form>
</div>
<style type="text/css">
    #iframe {
        margin-top: 15px;
    }

    #loading {
        -webkit-animation: loading 3s linear infinite;
        animation: loading 3s linear infinite;
    }

    @-webkit-keyframes loading {
        0% {
            color: rgba(34, 34, 34, 1);
        }
        50% {
            color: rgba(34, 34, 34, 0);
        }
        100% {
            color: rgba(34, 34, 34, 1);
        }
    }

    @keyframes loading {
        0% {
            color: rgba(34, 34, 34, 1);
        }
        50% {
            color: rgba(34, 34, 34, 0);
        }
        100% {
            color: rgba(34, 34, 34, 1);
        }
    }
</style>

<div class="tab-pane well" id="cache">
    <button class="btn btn-info" value="Удаление файлов" onclick="dellCacheFiles()">
        Удаление файлов
    </button>
</div>

<div class="tab-pane well" id="cmsFiles">
    <button class="btn btn-info" value="Проверка целостности файлов" onclick="checkCmsFiles()">
        Проверка целостности файлов
    </button>
    <span id="loading"></span>
    <div id="iframe">
    </div>
</div>


<script type="application/javascript">
    function dellCacheFiles()
    {
        var text = '';
        $.ajax({
            url: 'index.php',
            data: {action: 'dellCacheFiles', controller: 'Ideal\\Structure\\Service\\Cache', mode: 'ajax'},
            success: function (data)
            {
                if (data.text) {
                    text = 'Удалённые файлы кэша: <br />' + data.text;
                }
                else{
                    text = 'Информация о закэшированных страницах верна.';
                }
                $('.nav-tabs').parent().prepend('<div class="alert alert-block alert-success fade in">'
                    + '<button type="button" class="close" data-dismiss="alert">&times;</button>'
                    + '<span class="alert-heading">' + text + '</span></div>');
            },
            type: 'GET',
            dataType: "json"
        });
    }

    function checkCmsFiles()
    {
        $('#loading').html('Идёт сбор данных. Ждите.');
        $('#iframe').html('');
        var text = '';
        $.ajax({
            url: 'index.php',
            data: {action: 'checkCmsFiles', controller: 'Ideal\\Structure\\Service\\CheckCmsFiles', mode: 'ajax'},
            success: function (data)
            {
                if (data.newFiles) {
                    text += 'Были добавлены новые файлы: <br />' + data.newFiles;
                }
                if (data.changeFiles) {
                    if (text != '') {
                        text += '<br /><br />';
                    }
                    text += 'Были внесены изменения в следующие файлы: <br />' + data.changeFiles;
                }
                if (data.delFiles) {
                    if (text != '') {
                        text += '<br /><br />';
                    }
                    text += 'Были удалены следующие файлы: <br />' + data.delFiles;
                }
                if (text == '') {
                    text = 'Системные файлы соответствуют актуальной версии'
                }
                $('#loading').html('');
                $('#iframe').html(text);
            },
            error: function (xhr) {
                $('#loading').html('');
                $('#iframe').html('<pre> Не удалось завершить сканирование. Статус: '
                    + xhr.statusCode().status +
                    '\n Попробуйте повторить позже.</pre>');
            },
            type: 'GET',
            dataType: "json"
        });
    }
</script>
