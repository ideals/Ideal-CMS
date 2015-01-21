<?php
// Указание папки, где находится папка FormPhp
// define('PATHFORMPHP', '/cms-folder/Ideal/Library');

require_once 'autoloader.php';

$form = new FormPhp\Forms('myForm');

$form->add('name', 'text'); // добавляем одно текстовое поле ввода
$form->add('phone', 'text'); // добавляем одно текстовое поле ввода
$form->add('email', 'text'); // добавляем одно текстовое поле ввода
$form->setValidator('name', 'required'); // к полю ввода добавляем валидатор, требующий заполнить это поле
$form->setValidator('email', 'email'); // к полю ввода добавляем валидатор, требующий заполнить это поле
$form->setValidator('phone', 'phone'); // к полю ввода добавляем валидатор, требующий заполнить это поле

if ($form->isPostRequest()) {
    // Если отправлена форма, проверяем правильность её заполнения
    if ($form->isValid()) {
        echo 'Форма заполнена правильно<br />';
    } else {
        echo 'Форма заполнена неправильно<br />';
    }
}

$text = <<<TEXT
<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
<script type="text/javascript" src="jquery.placeholder.min.js"></script>
<script type="text/javascript" src="example.php?mode=js"></script>
<link media="all" rel="stylesheet" type="text/css" href="example.php?mode=css"/>
<form method="post" id="myForm">
    {$form->getTokenInput()}
    {$form->getValidatorsInput()}
    <label for="name">
        Просто текст
        <input type="text" name="name" placeholder="123"/>
        Просто телефон
        <input type="text" name="phone"/>
        Просто почта
        <input type="text" name="email"/>
    </label>
    <input type="submit">
</form>

TEXT;

$form->setText($text);

$form->render();
