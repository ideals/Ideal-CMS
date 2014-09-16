<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

if (!isset($_REQUEST['js']) || empty($_REQUEST['js'])) {
    die;
}
require_once('../[[SUBFOLDER]]/Ideal/Library/Minifier/Minifier.php');

$request = $_REQUEST['js'];

array_walk($request, 'Minifier::concat_prefix');
$min = new Minifier();
$min->merge($_SERVER['DOCUMENT_ROOT'] . '/js/all.min.js', $_SERVER['DOCUMENT_ROOT'] . '/js', $request);

die (file_get_contents($min->compressed));
