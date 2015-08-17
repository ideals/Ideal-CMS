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
</form>
