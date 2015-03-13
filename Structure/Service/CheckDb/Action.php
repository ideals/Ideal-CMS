<?php
namespace Ideal\Structure\Service\CheckDb;

use Ideal\Core\Config;
use Ideal\Core\Db;

echo '<ul class="nav nav-tabs">';

echo '<li class="active"><a href="#bd" data-toggle="tab">База данных</a></li>';
echo '<li><a href="#cache" data-toggle="tab">Кэш</a></li>';

echo '</ul>';
echo '<div class="tab-content">';

echo '<div class="tab-pane well active" id="bd">';

echo '<form method="POST" action="">';

$db = Db::getInstance();
$config = Config::getInstance();

$result = $db->select('SHOW TABLES');

$dbTables = array();
foreach ($result as $v) {
    $table = array_shift($v);
    if (strpos($table, $config->db['prefix']) === 0) {
        $dbTables[] = $table;
    }
}

$cfgTables = array();
$cfgTablesFull = array();
foreach ($config->structures as $v) {
    if (!$v['hasTable']) {
        continue;
    }
    list($module, $structure) = explode('_', $v['structure'], 2);
    $table = strtolower($config->db['prefix'] . $module . '_structure_' . $structure);
    $cfgTables[] = $table;

    // Обработка папки с кастомными шаблонами
    $dir = ($module == 'Ideal') ? $config->cmsFolder . '/Ideal.c/' : $config->cmsFolder . '/' . 'Mods.c/';
    $dir = stream_resolve_include_path($dir . $module . '/Addon');
    checkTypeFile($dir, $module, $cfgTables, $cfgTablesFull, $config);
    // Обработка папки с шаблонами
    $dir = ($module == 'Ideal') ? $config->cmsFolder . '/' : $config->cmsFolder . '/' . 'Mods/';
    $dir = stream_resolve_include_path($dir . $module . '/Addon');
    checkTypeFile($dir, $module, $cfgTables, $cfgTablesFull, $config);

    // Обработка папки с кастомными связующими таблицами
    $dir = ($module == 'Ideal') ? $config->cmsFolder . '/Ideal.c/' : $config->cmsFolder . '/' . 'Mods.c/';
    $dir = stream_resolve_include_path($dir . $module . '/Medium');
    checkTypeFile($dir, $module, $cfgTables, $cfgTablesFull, $config, 'Medium');
    // Обработка папки с связующими таблицами
    $dir = ($module == 'Ideal') ? $config->cmsFolder . '/' : $config->cmsFolder . '/' . 'Mods/';
    $dir = stream_resolve_include_path($dir . $module . '/Medium');
    checkTypeFile($dir, $module, $cfgTables, $cfgTablesFull, $config, 'Medium');

    $module = ($module == 'Ideal') ? '' : $module . '/';
    $cfgTablesFull[$table] = $module . 'Structure/' . $structure;
}

function checkTypeFile($dir, $module, &$cfgTables, &$cfgTablesFull, &$config, $type = 'Addon')
{
    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if (($file != '.') && ($file != '..') && (is_dir($dir . '/' . $file))) {
                $c = require($dir . '/' . $file . '/config.php');
                if (isset($c['params']['has_table']) && ($c['params']['has_table'] == false)) {
                    continue;
                }
                $t = strtolower($config->db['prefix'] . $module . '_' . $type . '_' . $file);
                if (array_search($t, $cfgTables) === false) {
                    $cfgTables[] = $t;
                    $cfgTablesFull[$t] = ($module == 'Ideal') ? $type . '/' . $file : $module . '/' . $type . '/' . $file;
                }
            }
        }
    }
}

// Если есть таблицы, которые надо создать
if (isset($_POST['create'])) {
    foreach ($_POST['create'] as $table => $v) {
        echo '<p>Создаём таблицу ' . $table . '…';
        $file = $cfgTablesFull[$table] . '/config.php';
        $data = include($file);
        $db->create($table, $data['fields']);
        echo ' Готово.</p>';
        $dbTables[] = $table;
    }
}

// Если есть таблицы, которые надо удалить
if (isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $table => $v) {
        echo '<p>Удаляем таблицу ' . $table . '…';
        $db->query("DROP TABLE `{$table}`");
        echo ' Готово.</p>';
        $key = array_search($table, $dbTables);
        unset($dbTables[$key]);
    }
}

$isCool = true;

foreach ($cfgTables as $table) {
    if (!in_array($table, $dbTables)) {
        echo '<p class="well"><input type="checkbox" name="create[' . $table . ']">&nbsp; ';
        echo 'Таблица <b>' . $table . '</b> отсутствует в базе данных. Создать?</p>';
        $isCool = false;
    }
}

foreach ($dbTables as $table) {
    if (!in_array($table, $cfgTables)) {
        echo '<p class="well"><input type="checkbox" name="delete[' . $table . ']">&nbsp; ';
        echo 'Таблица <b>' . $table . '</b> отсутствует в конфигурации. Удалить?</p>';
        $isCool = false;
    }
}

// После нажатия на кнопку применить и совершения действий, нужно либо заново перечитывать БД, либо перегружать страницу
if (isset($_POST['create']) || isset($_POST['delete'])) {
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($isCool) {
    echo 'Конфигурация в файлах соответствует конфигурации базы данных.';
} else {
    echo '<button class="btn btn-primary btn-large" type="submit">Применить</button>';
}
?>

</form>
</div>

<div class="tab-pane well" id="cache">
    <button class="btn btn-info" value="Удаление файлов" onclick="dellCacheFiles()">
        Удаление файлов
    </button>
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
                $('.nav-tabs').parent().prepend('<div class="alert alert-block alert-success fade in"> <button type="button" class="close" data-dismiss="alert">&times;</button><span class="alert-heading">' + text + '</span></div>');
            },
            type: 'GET',
            dataType: "json"
        });
    }
</script>
