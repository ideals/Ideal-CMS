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
        0% { color: rgba(34, 34, 34, 1); }
        50% { color: rgba(34, 34, 34, 0); }
        100% { color: rgba(34, 34, 34, 1); }
    }
    @keyframes loading {
        0% { color: rgba(34, 34, 34, 1); }
        50% { color: rgba(34, 34, 34, 0); }
        100% { color: rgba(34, 34, 34, 1); }
    }
</style>

<label class="checkbox">
    <input type="checkbox" name="force" id="force" />
    Принудительное составление xml-карты сайта
</label>

<button class="btn btn-info" value="Запустить сканирование" onclick="startSiteMap()">
    Запустить сканирование
</button>

<span id="loading"></span>

<div id="iframe">
</div>

<script type="application/javascript">
    function startSiteMap()
    {
        var param = '';
        if ($('#force').attr('checked') == 'checked') {
            param = '?w=1';
        }
        $('#loading').html('Идёт составление карты сайта. Ждите.');
        $('#iframe').html('<iframe src="Ideal/Library/sitemap/index.php' + param + '" onLoad="finishLoad()" />');
    }

    function finishLoad()
    {
        $('#loading').html('');
    }
</script>