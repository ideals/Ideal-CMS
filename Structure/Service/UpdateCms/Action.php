<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

/**
 * Сервис обновления IdealCMS и модулей
 *
 * ЧАСТЬ ПЕРВАЯ. ОТОБРАЖЕНИЕ УСТАНОВЛЕННЫХ ВЕРСИЙ И ВОЗМОЖНОСТЕЙ ОБНОВЛЕНИЯ
 * 1. Проверка файла update.log на возможность записи
 * 2. Считываем номера версий CMS и модулей из update.log
 * 3. Если update.log пуст или не содержит обновлений о каком-либо модуле вносим в него данные из README.md
 *    в формате
 *      Installed Наименование-папки-модуля v. Версия
 * 4. С сервера обновлений считываем доступные обновления и отображаем их в виде кнопок обновления по отдельности
 *    для каждого модуля
 *
 * ЧАСТЬ ВТОРАЯ. ОБНОВЛЕНИЕ МОДУЛЯ
 * 1. По нажатию на кнопку обновления у CMS или модуля скачиваем и распаковываем новую версию модуля
 * 2. Из update.log считываем последнюю установленную версию и последний установленный скрипт модуля
 * 3. Читаем список папок скриптов обновления, сортируем их по номеру версии
 * 4. В цикле по папкам читаем их содержимое, сортируем
 * 5. Выполняем скрипты в этих папках начиная со следующего после установленного скрипта
 */
?>

<p id="message">
    Внимание! Обновление в рамках одинакового первого номера происходит автоматически.<br/>
    Обновление на другой первый номер версии требует ручного вмешательства.<br/>
<hr/>
</p>

<?php
// Сервер обновлений
$getVersionScript = 'http://idealcms.ru/update/version.php';

$config = \Ideal\Core\Config::getInstance();
$versions = new \Ideal\Structure\Service\UpdateCms\Versions();

// Получаем установленные версии CMS и модулей
$nowVersions = $versions->getVersions();

$domain = urlencode($config->domain);

// Сервер обновлений
$url = $getVersionScript . '?domain=' . $domain . '&ver=' . urlencode(serialize($nowVersions));

// Переводим информацию о версиях в формат json для передачи в JS
$nowVersions = json_encode($nowVersions);

// Подключаем диалоговое окно
include('modalUpdate.html');

$msg = $versions->getAnswer();
if (count($msg['message'])) {
    foreach ($msg['message'] as $m) {
        switch ($m[1]) {
            case ('error'):
                $classBlock = 'alert alert-danger fade in';
                break;
            case ('info'):
                $classBlock = 'alert alert-info fade in';
                break;
            case ('success'):
                $classBlock = 'alert alert-success fade in';
                break;
            case ('warning'):
                $classBlock = 'alert alert-warning fade in';
                break;
            default:
                $classBlock = 'alert alert-info fade in';
        }
        echo "<div class=\"{$classBlock}\">{$m[0]}</div>\n";
    }
}
?>

<div id="form-input"></div>

<!-- Подключаем библиотеку для использования JSONP -->
<script type="text/javascript" src="Ideal/Structure/Service/UpdateCms/jquery.jsonp-2.4.0.min.js"> </script>

<!-- Передаём в JS необходимые переменные -->
<script type="text/javascript">
    var urlSrv = '<?php echo $url; ?>';
    var nowVersions = '<?php echo  $nowVersions ?>';
    var url = '<?php echo $_GET['par']; ?>';
</script>

<!-- Подключаем ajax скрипты -->
<script type="text/javascript" src="Ideal/Structure/Service/UpdateCms/js.js"> </script>
