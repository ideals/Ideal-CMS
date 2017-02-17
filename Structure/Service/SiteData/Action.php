<form action="" method=post enctype="multipart/form-data">

    <?php
    $config = \Ideal\Core\Config::getInstance();
    $file = new \Ideal\Structure\Service\SiteData\ConfigPhp();

    $file->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php');

    if (isset($_POST['edit'])) {
        $fileCache = new \Ideal\Structure\Service\Cache\Model($file);
        $response = $fileCache->checkSettings();
        $file->changeAndSave(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php', $response['res'], $response['class'], $response['text']);
    }
    echo $file->showEdit();
    ?>

    <br/>

    <input type="submit" class="btn btn-info" name="edit" value="Сохранить настройки"/>
    <script type="text/javascript">
        var hash = window.location.hash;
        if (hash) {
            $("a[href='" + hash + "']").click();
        }
        // Обновление данных для связи с сервисом "Яндекс.Вебмастер"
        function setDataYW()
        {
            $.ajax({
                type: "POST",
                dataType: 'json',
                url: '/?mode=ajax&controller=Ideal\\Addon\\YandexWebmaster&action=updateSettings',
                cache: false,
                success: function (data) {
                    if ('create_app' in data) {
                        if (confirm('В настройках отсутствует идентификатор приложения для связи с сервисом "Яндекс.Вебмастер". Перейти на страницу создания нового приложения?')) {
                            window.location.href = data.create_app;
                        }
                    }
                    if ('update_token' in data) {
                        if (confirm('Отсутствует токен для связи с сервисом "Яндекс.Вебмастер". Обновить токен?')) {
                            window.location.href = data.update_token;
                        }
                    }
                    if ('message' in data) {
                        alert(data.message);
                    }
                }
            });
            return false;
        }
    </script>
</form>
