<?php
// Добавление правил в файл ".htaccess" для отдачи закэшированных страниц.
use Ideal\Core\Config;

$config = Config::getInstance();
$configCache = $config->cache;

$fileName = DOCUMENT_ROOT . '/.htaccess';

$file = file_get_contents($fileName);

if (!is_writable($fileName)) {
    echo 'Файл ' . $fileName . ' недоступен для записи';
    exit;
}

if (mb_strpos($file, 'редирект на страницу без параметра в случае запроса первой страницы списка с параметром')) {
    // правила уже добавлена, значит ничего делать не надо
    return;
}

// Заменяем windows-переводы строки на unix-style
$file = mb_ereg_replace("\r\n", "\n", $file);

$basicFragment = '# file cache redirects';
$additionalFragment = 'RewriteRule ^.*$ /_.php [NC,L]';

$addText = <<<PHP
# редирект на страницу без параметра в случае запроса первой страницы списка с параметром
RewriteCond %{QUERY_STRING} !(.*)page=1[0-9]+(.*)$
RewriteCond %{QUERY_STRING} (.*)page=1(.*)$
RewriteRule ^(.*)$ $1?%1%2 [R=301,L]


PHP;

if (mb_strpos($file, $basicFragment)) {
    $insertPos = mb_strpos($file, $basicFragment);
} else {
    $insertPos = mb_strpos($file, $additionalFragment);
}

$start = mb_substr($file, 0, $insertPos);
$end = mb_substr($file, $insertPos);

$result = $start . $addText . $end;

file_put_contents($fileName, $result);
