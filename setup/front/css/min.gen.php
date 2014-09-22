<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

if (!isset($_REQUEST['css']) || empty($_REQUEST['css'])) {
    die;
}
require_once('../[[SUBFOLDER]]/Ideal/Library/Minifier/Minifier.php');

$request = $_REQUEST['css'];

array_walk($request, 'Minifier::concat_prefix');
$min = new Minifier();
$min->merge($_SERVER['DOCUMENT_ROOT'] . '/css/all.min.css', $_SERVER['DOCUMENT_ROOT'] . '/css', $request);
header('Content-type: text/css');
die (file_get_contents($min->compressed));
