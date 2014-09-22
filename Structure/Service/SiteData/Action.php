<form action="" method=post enctype="multipart/form-data">

    <?php
    $config = \Ideal\Core\Config::getInstance();
    $file = new \Ideal\Structure\Service\SiteData\ConfigPhp();

    $file->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php');

    if (isset($_POST['edit'])) {
        $file->saveFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php');
        if (!isset($_POST['general_cms_enabledMin'])) {
            if (file_exists(DOCUMENT_ROOT.'/js/all.min.js')) {
                unlink(DOCUMENT_ROOT.'/js/all.min.js');
            }
            if (file_exists(DOCUMENT_ROOT.'/css/all.min.css')) {
                unlink(DOCUMENT_ROOT.'/css/all.min.css');
            }
        }
    }

    echo $file->showEdit();
    ?>

    <br/>

    <input type="submit" class="btn btn-info" name="edit" value="Сохранить настройки"/>
</form>
