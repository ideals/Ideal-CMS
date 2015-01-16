<?php
require_once 'autoloader.php';

$form = new FormPhp\Forms('myForm');

$form->add('name', 'text'); // добавляем одно текстовое поле ввода
$form->setValidator('name', 'required'); // к полю ввода добавляем валидатор, требующий заполнить это поле

if ($form->isPostRequest()) {
    // Если отправлена форма, проверяем правильность её заполнения
    if ($form->isValid()) {
        echo 'Форма заполнена правильно<br />';
    } else {
        echo 'Форма заполнена неправильно<br />';
    }
}

$text = <<<TEXT
<script type="text/javascript" src="http://gradodel/js/jquery/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="http://gradodel/grad/Ideal/Library/FormPhp/example.php?mode=js"></script>
<form method="post" id="myForm">
    {$form->getTokenInput()}
    {$form->getValidatorsInput()}
    <label for="name">
        Просто текст
        <input type="text" name="name"/>
    </label>
    <input type="submit">
</form>

TEXT;

$form->setText($text);

$form->render();
