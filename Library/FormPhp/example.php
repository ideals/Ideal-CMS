<?php
/**
 * Есть два варианта подключения фреймворка форм:
 * 1. Без окружения Ideal CMS (более быстрый)
 * 2. С подключение автозагрузчика Ideal CMS
 */

// 1 ВАРИАНТ
// Указание папки, где находится папка FormPhp от корня сервера
// define('PATHFORMPHP', '/cms-folder/Ideal/Library');
// require_once 'autoloader.php';

// 2 ВАРИАНТ
$isConsole = true;
require_once $_SERVER['DOCUMENT_ROOT'] . '/_.php';

// Начало работы с фреймворком форм

$form = new FormPhp\Forms('myForm');

$form->add('name', 'text'); // добавляем одно текстовое поле ввода
$form->add('phone', 'text'); // добавляем одно текстовое поле ввода
$form->add('email', 'text'); // добавляем одно текстовое поле ввода
$form->setValidator('name', 'required'); // к полю ввода добавляем валидатор, требующий заполнить это поле
$form->setValidator('email', 'email'); // к полю ввода добавляем валидатор, требующий заполнить это поле
$form->setValidator('phone', 'phone'); // к полю ввода добавляем валидатор, требующий заполнить это поле
$form->add('file', 'fileMulti', array('id' => 'fileMyForm')); // добавляем поле для загрузки файлов

if ($form->isPostRequest()) {
    // Если отправлена форма, проверяем правильность её заполнения
    if ($form->isValid()) {
        $body = <<<HTML
Имя: {$form->getValue('name')}<br />
Телефон: {$form->getValue('phone')}<br />
Email: {$form->getValue('email')}
HTML;
        // Отправляем письмо пользователю
        $topic = 'Вы заполнили форму на сайте example.com';
        $form->sendMail('robot@example.com', $form->getValue('email'), $topic, $body, true);

        // Отправляем письмо менеджеру
        $topic = 'Вы заполнили форму на сайте example.com';
        $form->sendMail('robot@example.com', 'manager@example.com', $topic, $body, true);

        echo 'Форма заполнена правильно<br />';
    } else {
        echo 'Форма заполнена неправильно<br />';
    }
}
// todo перенести прикрепление формы к плагину и здание опций в класс Form
$script = <<<JS
$('#myForm').form({ajaxUrl : "/example.php"});
JS;
$form->setJs($script);

$mailTitle = 'Заявка';
$ymOnClick = 'SEND-FORM';
$ymOnSend = 'SENT-FORM';
$yaCounter = 'yaCounter17315254';
$text = <<<TEXT
<script type="text/javascript"
        src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
<script type="text/javascript"
        src="https://cdnjs.cloudflare.com/ajax/libs/jquery-placeholder/2.0.8/jquery.placeholder.min.js"></script>
<script type="text/javascript"
        src="example.php?mode=js"></script>
<link media="all" rel="stylesheet" type="text/css" href="example.php?mode=css"/>
<form method="post" id="myForm" data-click="{$ymOnClick}" data-send="{$ymOnSend}">
    {$form->start()}
    <input type="hidden" value="{$mailTitle}" name="mailTitle">
    <input type="hidden" value="{$yaCounter}" name="_yaCounter">
    <div>
    <label for="name">Просто текст</label>
    <input type="text" name="name" placeholder="123"/>
    </div>
    <div>
    <label for="name">Просто телефон</label>
    <input type="text" name="phone"/>
    </div>
    <div>
    <label for="name">Просто почта</label>
    <input type="text" name="email"/>
    </div>
        {$form->fields['file']->getInputText()}
    <input type="submit">
</form>

TEXT;

$form->setText($text);

$form->render();
