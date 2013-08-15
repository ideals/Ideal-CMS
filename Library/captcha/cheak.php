<?php

if (isset($_GET['code']) == false) {
    return;
}

$c = $_POST['code'];
$c = md5($c);
session_start();

if ($c == $_SESSION['cryptcode']) {
    $_SESSION['cryptcptuse'] = 0;
} else {
    print 'error';
}