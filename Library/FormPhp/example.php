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

// Если включено файловое кэширование, то необходимо установить изначальную валидацию по странице отправки формы
//$form->setLocationValidation(true);

// Устанавливаем "Тип заказа" для формы
$form->setOrderType('Заявка с сайта');

// Устанавливаем цели Метрики срабатывающие на клике на кнопку и на отправке формы
$form->setClickAndSend('SEND-FORM', 'SENT-FORM');

// Устанавливаем нормер счётчика Метрики
$form->setMetrika('yaCounter12345678');

// Устанавливаем Google Tag Manager
$form->setGtm();

// Устанавливаем url, используемый при ajax-отправке формы
$form->setAjaxUrl('/sendForm.php');

// Устанавливаем надобность отправки формы через ajax, по умолчанию "true"
// $form->setAjaxSend(false);


$form->add('name', 'text'); // добавляем одно текстовое поле ввода
$form->add('phone', 'text'); // добавляем одно текстовое поле ввода
$form->add('email', 'text'); // добавляем одно текстовое поле ввода
$form->add('file', 'fileMulti', array('id' => 'fileMyForm')); // добавляем поле для загрузки файлов

// Если нужно подключить капчу от Google, то добавляем следующее поле.
// Предварительно регистрируем сайт в Google https://www.google.com/recaptcha/admin
// В параметре "siteKey" должно быть передано значение из поля "Ключ" (поля на странице настройки капчи).
// ВАЖНО!!! Название поля всегда должно быть "g-recaptcha-response"
$form->add('g-recaptcha-response', 'reCaptcha', array('siteKey' => ''));

// Для вывода поля на форме можно использовать следующую конструкцию.
// $form->fields['g-recaptcha-response']->getInputText()

$form->setValidator('name', 'required'); // к полю ввода добавляем валидатор, требующий заполнить это поле
$form->setValidator('email', 'email'); // к полю ввода добавляем валидатор, требующий заполнить это поле
$form->setValidator('phone', 'phone'); // к полю ввода добавляем валидатор, требующий заполнить это поле

// Для проверки капчи от Google добавляем соответствующий валидатор.
// В параметре "secretKey" должно быть передано значение из поля "Секретный ключ" (поля на странице настройки капчи).
$form->setValidator('g-recaptcha-response', 'reCaptcha', array('secretKey' => ''));



if ($form->isPostRequest()) {
    // Если отправлена форма, проверяем правильность её заполнения
    if ($form->isValid()) {
        $body = <<<HTML
Имя: {$form->getValue('name')}<br />
Телефон: {$form->getValue('phone')}<br />
Email: {$form->getValue('email')}
HTML;

        // Если требуется отправка через SMTP, то передаём дополнительные сведения в метод отправки
        $smtp = array(
            'domain' => 'example.org',
            'server' => 'smtp.example.org',
            'port' => '25',
            'user' => 'robot@example.org',
            'password' => 'password');
        // Если отправка через SMTP не требуется, то передавать дополнительный параметр не нужно

        // Отправляем письмо пользователю
        $topic = 'Вы заполнили форму на сайте example.com';
        $form->sendMail('robot@example.com', $form->getValue('email'), $topic, $body, true, $smtp);

        // Отправляем письмо менеджеру с добавлением источника перехода
        $topic = 'Заявка с сайта example.com';
        $body .= '<br />Источник перехода: ' . $form->getValue('referer');
        $form->sendMail('robot@example.com', 'manager@example.com', $topic, $body, true, $smtp);

        // Сохраняем информацию о заказе, только если используется второй вариант подключения фреймворка форм.
        $form->saveOrder($form->getValue('name'), $form->getValue('email'));

        echo 'Форма заполнена правильно<br />';
    } else {
        echo 'Форма заполнена неправильно<br />';
    }
}

$text = <<<HTML
<script type="text/javascript"
        src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
<script type="text/javascript"
        src="https://cdnjs.cloudflare.com/ajax/libs/jquery-placeholder/2.0.8/jquery.placeholder.min.js"></script>
<script type="text/javascript"
        src="example.php?mode=js"></script>
<link media="all" rel="stylesheet" type="text/css" href="example.php?mode=css"/>
{$form->start()}
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

HTML;

$form->setText($text);

$form->render();
