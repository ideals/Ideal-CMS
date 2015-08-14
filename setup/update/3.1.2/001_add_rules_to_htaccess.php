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

if (mb_strpos($file, 'file cache redirects')) {
    // правила уже добавлена, значит ничего делать не надо
    return;
}

// Заменяем windows-переводы строки на unix-style
$file = mb_ereg_replace("\r\n", "\n", $file);

$basicFragment = '# Если файла на диске нет, вызывается скрипт';
$additionalFragment = 'RewriteRule ^.*$ /_.php [NC,L]';

$addText = <<<PHP
# file cache redirects
# Запрашивается главная страница
RewriteCond %{DOCUMENT_ROOT}/tmp/cache/fileCache/{$configCache['indexFile']} -f [OR]
RewriteCond %{DOCUMENT_ROOT}/tmp/cache/fileCache/{$configCache['indexFile']} -l
RewriteRule ^$ tmp/cache/fileCache/{$configCache['indexFile']} [NC,L]

# Запрашивается внутренняя страница при установленном суффиксе "/" или при отсуствии суффикса
RewriteCond %{DOCUMENT_ROOT}/tmp/cache/fileCache/%{REQUEST_URI}/{$configCache['indexFile']} -f [OR]
RewriteCond %{DOCUMENT_ROOT}/tmp/cache/fileCache/%{REQUEST_URI}/{$configCache['indexFile']} -l
RewriteRule ^(.*)$ tmp/cache/fileCache/$1/{$configCache['indexFile']} [NC,L]

# Запрашивается внутреняя страница при произвольно установленном суффиксе
RewriteCond %{DOCUMENT_ROOT}/tmp/cache/fileCache/%{REQUEST_URI} -f [OR]
RewriteCond %{DOCUMENT_ROOT}/tmp/cache/fileCache/%{REQUEST_URI} -d [OR]
RewriteCond %{DOCUMENT_ROOT}/tmp/cache/fileCache/%{REQUEST_URI} -l
RewriteRule ^(.*)$ tmp/cache/fileCache/$1 [NC,L]
# file cache redirects

[[BASIC FRAGMENT]]
RewriteRule ^.*$ /_.php [NC,L]
PHP;

if (mb_strpos($file, $basicFragment)) {
    $insertPos = mb_strpos($file, $basicFragment);
    $addText = str_replace('[[BASIC FRAGMENT]]', $basicFragment, $addText);
} else {
    $insertPos = mb_strpos($file, $additionalFragment);
    $addText = str_replace('[[BASIC FRAGMENT]]', '', $addText);
}

$start = mb_substr($file, 0, $insertPos);

$result = $start . $addText;

file_put_contents($fileName, $result);
