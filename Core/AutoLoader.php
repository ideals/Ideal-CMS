<?php

error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING); //| E_STRICT

setlocale(LC_ALL, 'ru_RU.UTF8');

// Для PHP5 нужно установить часовой пояс
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Europe/Moscow');
}

/**
 * Обработчик обычных ошибок скриптов. Реакция зависит от настроек $config->errorLog
 *
 * @param $errno   Номер ошибки
 * @param $errstr  Сообщение об ошибке
 * @param $errfile Имя файла, в котором была ошибка
 * @param $errline Номер строки на которой произошла ошибка
 */
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    $_err = 'Ошибка [' . $errno . '] ' . $errstr . ', в строке ' . $errline . ' файла ' . $errfile;
    \Ideal\Core\Util::addError($_err, false);
}

set_error_handler('myErrorHandler');

/**
 * Обработчик, вызываемый при завершении работы скрипта.
 * Используется для обработки ошибок, которые не перехватывает set_error_handler()
 * Реакция зависит от настроек $config->errorLog
 */
function shutDownFunction()
{
    $error = error_get_last();
    $errors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING);
    if (in_array($error['type'], $errors)) {
        $_err = 'Ошибка ' . $error['message'] . ', в строке ' . $error['line'] . ' файла ' . $error['file'];
        \Ideal\Core\Util::addError($_err, false);
    }
    \Ideal\Core\Util::shutDown();
}

register_shutdown_function('shutdownFunction');

mb_internal_encoding('UTF-8'); // наша кодировка всегда UTF-8

if (get_magic_quotes_gpc()) {
    die('Включены magic_quotes! Отключите их в настройках хостинга, иначе система работать не будет.');
}

/**
 * Обработчик автозагрузки
 * Все классы Ideal CMS вызываются по следующей схеме:
 * ModuleName/Class_Name
 * где ModuleName — это название модуля (и соответствующее пространство имён)
 *
 * @param $className Имя класса, которое не нашлось в пространстве имён
 * @return bool Флаг успешного подключения файла класса
 */
function autoLoad($className)
{
    $className = ltrim($className, '\\');

    if (strpos($className, '\\') === false) {
        // Имя класса без namespace — значит это не наш класс
        return false;
    }

    $elements = explode('\\', $className);
    $end = explode('_', array_pop($elements));
    $elements = array_merge($elements, $end);

    if ($elements[0] == 'Ideal') {
        // Если нэймспейс Ideal - убираем его из массива
        array_shift($elements);
    }
    array_pop($elements); // убираем последний элемент массива — имя файла

    $folder = implode(DIRECTORY_SEPARATOR, $elements);
    //array_shift($end); // убираем первый элемент из имени файла — тип данных
    $file = implode('', $end);
    $fileName = $folder . DIRECTORY_SEPARATOR . $file . '.php';
    $fileName = stream_resolve_include_path($fileName);

    if ($fileName !== false) {
        require_once $fileName;
        return true;
    }

    // Если файл не удалось подключить — обработка уйдёт дальше по стеку автозагрузки
    return false;
}

spl_autoload_register('autoLoad', true);