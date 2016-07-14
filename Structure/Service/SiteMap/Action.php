<style>
    #iframe {
        margin-top: 15px;
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

if (!$file->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_map.php')) {
    // Если не удалось прочитать данные из кастомного файла, значит его нет
    // Поэтому читаем данные из демо-файла
    $file->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/Library/sitemap/site_map_demo.php');
    $params = $file->getParams();
    $params['default']['arr']['website']['value'] = 'http://' . $config->domain;
    $file->setParams($params);
}

if (isset($_POST['edit'])) {
    $file->changeAndSave(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_map.php');
}
?>

<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li class="active"><a href="#settings" data-toggle="tab">Настройки</a></li>
    <li><a href="#start" data-toggle="tab">Запуск карты сайта</a></li>
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
        <h3>Запуск карты сайта вручную</h3>
        <label class="checkbox">
            <input type="checkbox" name="force" id="force"/>
            Принудительное составление xml-карты сайта
        </label>
        <label class="checkbox">
            <input type="checkbox" name="clear-temp" id="clear-temp"/>
            Сброс ранее собранных страниц
        </label>

        <button class="btn btn-info" value="Запустить сканирование" onclick="startSiteMap()">
            Запустить сканирование
        </button>

        <span id="loading"></span>

        <div id="iframe">
        </div>

        <div>
            <p>&nbsp;</p>
            <h3>Запуск карты сайта через cron</h3>
            <p>Чтобы прописать в cron'е команду на запуск составления карты сайта в терминале выполните команду:</p>
            <pre><code>crontab -e</code></pre>
            <p>Далее в открывшемся редакторе запишите такую строку:</p>
            <pre><code>*/3 2-4 * * * /usr/bin/php <?php
                    echo DOCUMENT_ROOT . '/' . $config->cmsFolder; ?>/Ideal/Library/sitemap/index.php</code></pre>
            <p>Эта инструкция означает запуск скрипта каждые три минуты с двух до четырёх ночи.
                Если этого времени не хватает для составления карты сайта, то можно увеличить диапазон часов.</p>
        </div>
    </div>
</div>

<script type="application/javascript">
    function startSiteMap() {
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
        $('#loading').html('Идёт составление карты сайта. Ждите.');
        $('#iframe').html('');
        getSitemapAjaxify(param);
    }

    function getSitemapAjaxify(param) {
        $.ajax({
            url: 'Ideal/Library/sitemap/index.php' + param,
            success: function (data) {
                $('#iframe').append(data);
                if (/Выход по таймауту/gim.test(data)) {
                    param = param.replace('?с=1', '');
                    param = param.replace('&с=1', '');
                    getSitemapAjaxify(param);
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
                        getSitemapAjaxify(param);
                    }, 10000);
            },
            type: 'get'
        });
    }

    function finishLoad() {
        $('#loading').html('');
    }
</script>