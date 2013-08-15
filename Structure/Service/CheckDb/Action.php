<?php
namespace Ideal\Structure\Service\CheckDb;

use Ideal\Core\Config;
use Ideal\Core\Db;

echo '<form method="POST" action="">';

$db = Db::getInstance();
$config = Config::getInstance();

$result = $db->queryArray('SHOW TABLES');

$dbTables = array();
foreach($result as $v) {
    $table = array_shift($v);
    if (strpos($table, $config->db['prefix']) === 0) {
        $dbTables[] = $table;
    }
}

$cfgTables = array();
$cfgTablesFull = array();
foreach($config->structures as $v) {
    if (!$v['hasTable']) {
        continue;
    }
    list($module, $structure) = explode('_', $v['structure'], 2);
    $table = strtolower($config->db['prefix'] . $module . '_structure_' . $structure);
    $cfgTables[] = $table;
    $module = ($module == 'Ideal') ? '' : $module . '/';
    $cfgTablesFull[$table] = $module . 'Structure/' . $structure;
}

// Обработка системной папки с шаблонами
// TODO сделать проверку папок с шаблонами в модулях
$dir = stream_resolve_include_path($config->cmsFolder . '/Ideal/Template');
if ($handle = opendir($dir)) {
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            if (is_dir($dir . '/' . $file)) {
                $table = $config->db['prefix'] . 'ideal_template_' . strtolower($file);
                $cfgTables[] = $table;
                $cfgTablesFull[$table] = 'Template/' . $file;
            }
        }
    }
}

// Обработка кастомной папки с шаблонами
$dir = stream_resolve_include_path($config->cmsFolder.'/Custom/Template');
if ($handle = opendir($dir)) {
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            if (is_dir($dir . '/' . $file)) {
                $table = $config->db['prefix'] . 'ideal_template_' . strtolower($file);
                $cfgTables[] = $table;
                $cfgTablesFull[$table] = 'Template/' . $file;
            }
        }
    }
}

// Если есть таблицы, которые надо создать
if (isset($_POST['create'])) {
    foreach($_POST['create'] as $table => $v) {
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
    foreach($_POST['delete'] as $table => $v) {
        echo '<p>Удаляем таблицу ' . $table . '…';
        $db->query("DROP TABLE `{$table}`");
        echo ' Готово.</p>';
        $key = array_search($table, $dbTables);
        unset($dbTables[$key]);
    }
}

$isCool = true;

foreach($cfgTables as $table) {
    if (!in_array($table, $dbTables)) {
        echo '<p class="well"><input type="checkbox" name="create[' . $table . ']">&nbsp; ';
        echo 'Таблица <b>' . $table . '</b> отсутствует в базе данных. Создать?</p>';
        $isCool = false;
    }
}

foreach($dbTables as $table) {
    if (!in_array($table, $cfgTables)) {
        echo '<p class="well"><input type="checkbox" name="delete[' . $table . ']">&nbsp; ';
        echo 'Таблица <b>' . $table . '</b> отсутствует в конфигурации. Удалить?</p>';
        $isCool = false;
    }
}

// После нажатия на кнопку применить и совершения действий, нужно либо заново перечитывать БД, либо перегружать страницу
if (isset($_POST['create']) OR isset($_POST['delete'])) {
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