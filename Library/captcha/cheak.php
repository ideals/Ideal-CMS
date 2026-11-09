<?php

use Ideal\Core\Session;

if (isset($_GET['code']) === false) {
    return;
}

$c = $_POST['code'];
$c = md5($c);
Session::start();

if ($c == $_SESSION['cryptcode']) {
    $_SESSION['cryptcptuse'] = 0;
} else {
    print 'error';
}
