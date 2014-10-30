<?php
if (!isset($_REQUEST['[[TYPE]]']) || empty($_REQUEST['[[TYPE]]'])) die;
require_once('../[[SUBFOLDER]]/Ideal/Library/Minifier/Minifier.php');

$request = $_REQUEST['[[TYPE]]'];

array_walk($request, 'Minifier::concat_prefix');
$min = new Minifier();
$min->merge($_SERVER['DOCUMENT_ROOT'] . '/[[TYPE]]/all.min.[[TYPE]]' , $_SERVER['DOCUMENT_ROOT'].'/[[TYPE]]', $request);
die (file_get_contents($min->compressed));