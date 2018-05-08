<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
require_once __DIR__ . '/TurboClass.php';

$turbo = new YandexTurboPage\TurboClass();

$message = '';
try {
    $turbo->run();
} catch (Exception $e) {
    $message = $e->getMessage();
}

echo $message;
