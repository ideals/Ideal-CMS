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

<form method="post" name="myForm">
    <?= $form->getTokenInput() ?>
    <label for="name">
        Просто текст
        <input type="text" name="name"/>
    </label>
    <input type="submit">
</form>

TEXT;

$form->setText($text);

$form->render();
