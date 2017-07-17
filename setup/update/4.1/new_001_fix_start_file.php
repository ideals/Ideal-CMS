<?php

$fileName = DOCUMENT_ROOT . '/_.php';

$file = file_get_contents($fileName);

if (!is_writable($fileName)) {
    echo 'Файл ' . $fileName . ' недоступен для записи';
    exit;
}

if (mb_strpos($file, "'api'")) {
    // Роутинг по API уже добавлен
    return;
}

// Заменяем windows-переводы строки на unix-style
$file = mb_ereg_replace("\r\n", "\n", $file);

$fragment = 'if (strpos($_SERVER[\'REQUEST_URI\'], $config->cmsFolder . \'/\') === 1) {';

$addText = <<<PHP
if (strpos(\$_SERVER['REQUEST_URI'], 'api/') === 1) {
    // Обращение к api
    \$page->run('api');
} elseif (strpos(\$_SERVER['REQUEST_URI'], \$config->cmsFolder . '/') === 1) {
PHP;

$insertPos = mb_strpos($file, $fragment);

$start = mb_substr($file, 0, $insertPos);
$end = mb_substr($file, $insertPos + mb_strlen($fragment));

$result = $start . $addText . $end;

file_put_contents($fileName, $result);
