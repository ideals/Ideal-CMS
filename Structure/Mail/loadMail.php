<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

$isConsole = true;
require_once $_SERVER['DOCUMENT_ROOT'] . '/_.php';

try {
    $mail = new \Ideal\Structure\Mail\Adapter\Yandex();
    $mail->loadMail();
} catch (\Exception $e) {
    echo 'Ошибка: ' . $e->getMessage() . "\n";
}
