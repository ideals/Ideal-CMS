<?php
/*
 * Удаление файла
 */
if (isset($_POST['name'])) {
    exit(unlink($_POST['name']));
}
exit(false);