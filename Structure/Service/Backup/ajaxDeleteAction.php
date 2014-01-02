<?php
/*
 * Удаление файла
 */
if (isset($_POST['name'])) {
    exit(unlink(stream_resolve_include_path($_POST['name'])));
}
exit(false);