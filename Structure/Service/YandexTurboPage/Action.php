<style>
    #iframe {
        margin-top: 15px;
        white-space: pre-line;
    }

    #iframe iframe {
        width: 100%;
        border: 1px solid #E7E7E7;
        border-radius: 6px;
        height: 300px;
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

<?php
$config = \Ideal\Core\Config::getInstance();
$file = new \Ideal\Structure\Service\SiteData\ConfigPhp();

if (!$file->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_turbo.php')) {
    // Если не удалось прочитать данные из кастомного файла, значит его нет
    // Поэтому читаем данные из демо-файла
    $file->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/Library/YandexTurboPage/site_turbo_demo.php');
    $params = $file->getParams();

    $sitemap = array();
    if (file_exists(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_map.php')) {
        $sitemap = require_once DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_map.php';
        // Записываем путь до файла карты сайта по умолчанию в настройки
        $params['default']['arr']['sitemapFile']['value'] = $sitemap['sitemap_file'];
    }

    // Записываем сайт для сканирования по умолчанию в настройки
    $params['default']['arr']['website']['value'] = $sitemap ? $sitemap['website'] : 'http://' . $config->domain;

    // Записываем корневоую папку на диске
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        // Обнаружение корня сайта, если скрипт запускается из стандартного места в Ideal CMS
        $self = $_SERVER['PHP_SELF'];
        $path = substr($self, 0, strpos($self, 'Ideal') - 1);
        $params['default']['arr']['pageroot']['value'] = dirname($path);
    } else {
        $params['default']['arr']['pageroot']['value'] = $_SERVER['DOCUMENT_ROOT'];
    }

    // Записываем электронную почту для уведомлений об ошибках по умолчанию в настройки
    $params['default']['arr']['error_email_notify']['value'] = $config->cms['adminEmail'];

    // Записываем электронную почту для уведомлений менеджера по умолчанию в настройки
    $params['default']['arr']['manager_email_notify']['value'] = $config->mailForm;

    // Записываем путь до файла карты сайта
    $file->setParams($params);
}

if (isset($_POST['edit'])) {
    $file->changeAndSave(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_turbo.php');
}
?>

<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li class="active"><a href="#settings" data-toggle="tab">Настройки</a></li>
    <li><a href="#start" data-toggle="tab">Запуск Яндекс Турбо-страниц</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
    <div class="tab-pane active" id="settings">
        <form action="" method=post enctype="multipart/form-data">

            <?php echo $file->showEdit(); ?>

            <br/>

            <input type="submit" class="btn btn-info" name="edit" value="Сохранить настройки"/>
        </form>
    </div>
    <div class="tab-pane" id="start">
        <h3>Запуск Яндекс Турбо-страниц вручную</h3>
        <label class="checkbox">
            <input type="checkbox" name="force" id="force"/>
            Принудительное составление фида Турбо-страниц
        </label>
        <label class="checkbox">
            <input type="checkbox" name="clear-temp" id="clear-temp"/>
            Сброс ранее собранных данных
        </label>
        <button class="btn btn-info" value="Запустить сканирование" onclick="startYandexTurboFeed()">
            Запустить сбор фида
        </button>
        <span id="loading"></span>

        <div id="iframe">
        </div>
        <div>
            <p>&nbsp;</p>
            <h3>Запуск сбора фида Турбо-страниц для Яндекса через cron</h3>
            <p>Чтобы прописать в cron'е команду на запуск составления карты сайта в терминале выполните команду:</p>
            <pre><code>crontab -e</code></pre>
            <p>Далее в открывшемся редакторе запишите такую строку:</p>
            <pre><code>*/2 5 * * * /usr/bin/php <?php
                  echo DOCUMENT_ROOT . '/' . $config->cmsFolder; ?>/Ideal/Library/YandexTurboPage/index.php</code></pre>
            <p>Эта инструкция означает запуск скрипта каждые две минуты с пяти до шести ночи.
                Если этого времени не хватает для составления фида Турбо-страниц, то можно увеличить диапазон часов.</p>
</div>
    </div>
</div>

<script type="application/javascript">
    function startYandexTurboFeed() {
        var param = '';
        if ($('#force').prop('checked')) {
            param += '?w=1';
        }
        if ($('#clear-temp').prop('checked')) {
            if (param == '') {
                param += '?с=1';
            } else {
                param += '&с=1';
            }
        }
        $('#loading').html('Идёт составление фида Турбо-страниц. Ждите.');
        $('#iframe').html('');
        getYandexTurboFeedAjaxify(param);
    }

    function getYandexTurboFeedAjaxify(param) {
        var extParam = param;
        if (extParam == '') {
            extParam += '?timestamp=' + Date.now();
        } else {
            extParam += '&timestamp=' + Date.now();
        }
        $.ajax({
            url: 'Ideal/Library/YandexTurboPage/index.php' + extParam,
            success: function (data) {
                $('#iframe').append(data);
                if (/Выход по таймауту/gim.test(data)) {
                    param = param.replace('?с=1', '');
                    param = param.replace('&с=1', '');
                    getYandexTurboFeedAjaxify(param);
                } else {
                    finishLoad();
                }
            },
            error: function (xhr) {
                $('#iframe').append('<pre> Не удалось завершить сканирование. Статус: '
                    + xhr.statusCode().status +
                    '\n Попытка продолжить сканирование через 10 секунд.</pre>');
                setTimeout(
                    function () {
                        getYandexTurboFeedAjaxify(param);
                    }, 10000);
            },
            type: 'get'
        });
    }

    function finishLoad() {
        $('#loading').html('');
    }
</script>
