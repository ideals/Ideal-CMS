<?php
namespace Ideal\Structure\Service\Error404;

?>
<form action="" method=post enctype="multipart/form-data">
    <?php
    $config = \Ideal\Core\Config::getInstance();
    $file = new \Ideal\Structure\Service\SiteData\ConfigPhp();

    $file->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php');

    if (isset($_POST['edit'])) {
        $file->changeAndSave(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php');
    }

    echo $file->showEdit();
    ?>
    <br/>
    <input type="submit" class="btn btn-info" name="edit" value="Сохранить настройки"/>
</form>
