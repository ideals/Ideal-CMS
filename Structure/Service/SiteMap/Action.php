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

<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li class="active"><a href="#settings" data-toggle="tab">Настройки</a></li>
    <li><a href="#start" data-toggle="tab">Запуск карты сайта</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
    <div class="tab-pane active" id="settings">
        <form action="" method=post enctype="multipart/form-data">

            <?php
            $config = \Ideal\Core\Config::getInstance();
            $file = new \Ideal\Structure\Service\SiteData\ConfigPhp();

            $file->loadFile($config->cmsFolder . '/site_map.php');

            if (isset($_POST['edit'])) {
                $file->saveFile($config->cmsFolder . '/site_map.php');
            }

            echo $file->showEdit();
            ?>

            <br/>

            <input type="submit" class="btn btn-info" name="edit" value="Сохранить настройки"/>
        </form>
    </div>
    <div class="tab-pane" id="start">
        <label class="checkbox">
            <input type="checkbox" name="force" id="force"/>
            Принудительное составление xml-карты сайта
        </label>

        <button class="btn btn-info" value="Запустить сканирование" onclick="startSiteMap()">
            Запустить сканирование
        </button>

        <span id="loading"></span>

        <div id="iframe">
        </div>
    </div>
</div>


<script type="application/javascript">
    function startSiteMap() {
        var param = '';
        if ($('#force').attr('checked') == 'checked') {
            param = '?w=1';
        }
        $('#loading').html('Идёт составление карты сайта. Ждите.');
        $('#iframe').html('<iframe src="Ideal/Library/sitemap/index.php' + param + '" onLoad="finishLoad()" />');
    }

    function finishLoad() {
        $('#loading').html('');
    }
</script>