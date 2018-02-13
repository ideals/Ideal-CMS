<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

/**
 * Создаём дамп базы данных
 */
use Ideal\Core\Config;

if (isset($_POST['createMysqlDump'])) {
    // Подключаем библиотеку
    require_once 'Library/MySQLDump/mysqldump.php';

    $config = Config::getInstance();

    // Папка сохранения дампов
    $backupPart = stream_resolve_include_path($_POST['backupPart']);

    // Задаём параметры для создания бэкапа
    $dumpSettings = array(
        'compress' => 'GZIP',
        'no-data' => false,
        'add-drop-table' => true,
        'single-transaction' => false,
        'lock-tables' => false,
        'add-locks' => true,
        'extended-insert' => false
    );
    $dump = new Mysqldump(
        $config->db['name'],
        $config->db['login'],
        $config->db['password'],
        $config->db['host'],
        'mysql',
        $dumpSettings
    );

    $time = time();

    // Получаем версию админки
    $versions = new \Ideal\Structure\Service\UpdateCms\Versions();
    $nowVersions = $versions->getVersions();
    $version = 'v' . $nowVersions['Ideal-CMS'];

    // Имя файла дампа
    $dumpName = 'dump_' . date('Y.m.d_H.i.s', $time) . '_' . $version . '.sql';

    // Запускаем процесс выгрузки
    $tes = $dump->start($backupPart . DIRECTORY_SEPARATOR . $dumpName);

    $dumpName = $backupPart . DIRECTORY_SEPARATOR . $dumpName . '.gz';

    // Формируем строку с новым файлом
    echo '<tr id="' . $dumpName . '"><td><a href="" onClick="return downloadDump(\'' .
        addslashes($dumpName) . '\')"> ' .
        date('d.m.Y - H:i:s', $time) . ' - ' . $version
        . '</a></td>';
    echo '<td>'
    . '<button class="btn btn-info btn-xs" title="Импортировать" onclick="importDump(\'' .
        addslashes($dumpName) . '\'); return false;">'
        . '<span class="glyphicon glyphicon-upload"></span></button>&nbsp;'

    . '<button class="btn btn-danger btn-xs" title="Удалить" onclick="delDump(\'' .
        addslashes($dumpName) . '\'); return false;">'
        . '<span class="glyphicon glyphicon-remove"></span></button>&nbsp;'

    . '<button id="' . $dumpName . '_btn_cmt"
            class="tlp btn btn-default btn-xs btn-cmt"
            title="Добавить комментарий"
            onclick="showModal(\'' . addslashes($dumpName) . '\'); false;">'
        . '<span class="glyphicon glyphicon-pencil"></span></button>&nbsp;';

    echo '</td></tr>';
}

exit(false);
