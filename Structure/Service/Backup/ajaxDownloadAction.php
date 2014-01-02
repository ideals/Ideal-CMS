<?php
/*
 * Скачивание файла
 */
header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

if (!isset($_GET['file'])) {
    header('Content-Disposition: attachment; filename=file-name-not-set');
    header('Content-Length: 0');
    exit;
}

if (!is_file($_GET['file'])) {
    header('Content-Disposition: attachment; filename=NOT-FOUND-' . basename($_GET['file']));
    header('Content-Length: 0');
    exit;
}

header('Content-Disposition: attachment; filename=' . basename($_GET['file']));
header('Content-Length: ' . filesize($_GET['file']));

ob_clean();
flush();
readfile($_GET['file']);

exit;
