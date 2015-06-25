<?php

$fileName = DOCUMENT_ROOT . '/_.php';

$file = file_get_contents($fileName);

if (!is_writable($fileName)) {
    echo 'Файл ' . $fileName . ' недоступен для записи';
    exit;
}

if (mb_strpos($file, '$isConsole')) {
    // переменная уже добавлена, значит ничего делать не надо
    return;
}

$fragment = '$config->loadSettings();' . "\n";

$addText = <<<PHP

if (isset(\$isConsole)) {
    // Если инициализированная переменная \$isConsole, значит этот скрипт используется
    // только для инициализации окружения
    return;
}

PHP;

$insertPos = mb_strpos($file, $fragment) + mb_strlen($fragment);

$start = mb_substr($file, 0, $insertPos);
$end = mb_substr($file, $insertPos);

$result = $start . $addText . $end;

file_put_contents($fileName, $result);
