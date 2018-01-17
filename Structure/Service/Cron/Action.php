<?php
$config = \Ideal\Core\Config::getInstance();
$file = new \Ideal\Structure\Service\SiteData\ConfigPhp();
$configFile = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/crontab';
$data = file_exists($configFile) ? file_get_contents($configFile) : '';

if (isset($_POST['crontab'])) {
    // todo сделать тестирование задач крона и если некорректно, то сообщать об ошибках
    file_put_contents($configFile, $_POST['crontab']);
}
?>
<div>
    <h3>Управление задачами по расписанию из административной части</h3>

    <form action="" method=post enctype="multipart/form-data">
        <div id="general_cron_crontab-control-group" class="form-group">
            <label class=" control-label" for="general_cron_crontab">Установленные задачи крона:</label>
            <div class=" general_cron_crontab-controls">
                <textarea class="form-control" name="crontab"
                          placeholder="Задачи не установлены. Формат: * * * * * path/to/script.php"
                          id="crontab" rows="5"><?= $data; ?></textarea>
                <div id="general_cron_crontab-help"></div>
            </div>
        </div>
        <input type="submit" class="btn btn-info" name="edit" value="Сохранить"/>
    </form>

    <p>&nbsp;</p>

    <p>
        Формат аналогичен системному cron'у, но указываем только название скрипта.<br>
        Если не указывать начальный слэш у выполняемого скрипта, то он будет подключаться от корня сайта.
    </p>

    <h4>Краткая справка по настройке системного крона</h4>

    <p>
        Чтобы управлять выполнением задач по расписанию из административной части необходимо в системном cron'е
        прописать запуск скрипта отвечающего за обработку этих задач.
    </p>
    <p>Для этого в терминале выполните команду:</p>
    <pre><code>/usr/bin/php <?php
            echo DOCUMENT_ROOT . '/' . $config->cmsFolder; ?>/Ideal/Library/cron/cron.php test</code></pre>
    <p>
        Если тестовый запуск прошёл успешно, то можно встраивать запуск этой задачи в системный cron.
        Для этого выполните команду:
    </p>
    <pre><code>crontab -e</code></pre>
    <p>Далее в открывшемся редакторе запишите такую строку:</p>
    <pre><code>* * * * * /usr/bin/php <?php
            echo DOCUMENT_ROOT . '/' . $config->cmsFolder; ?>/Ideal/Library/cron/cron.php</code></pre>
    <p>
        Эта инструкция означает запуск скрипта каждую минуту.
        Если это будет сильно нагружать сервер, то можно сделать запуск скрипта реже.
        Задачи прописанные в поле ниже будут выполнены в момент запуска скрипта обработчика задач,
        даже если их время прошло (если они ещё не запускались)
    </p>
</div>
