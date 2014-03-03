<form action="" method=post enctype="multipart/form-data">

<?php
$config = \Ideal\Core\Config::getInstance();
$file = new \Ideal\Structure\Service\SiteData\ConfigPhp();

$file->loadFile($config->cmsFolder . '/site_data.php');

if (isset($_POST['edit'])) {
    $file->saveFile($config->cmsFolder . '/site_data.php');
}

echo $file->showEdit();
?>

    <br />

    <input type="submit" class="btn btn-info" name="edit" value="Сохранить настройки" />
</form>
