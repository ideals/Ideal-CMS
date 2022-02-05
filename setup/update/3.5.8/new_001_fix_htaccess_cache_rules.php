<?php
// Изменение правил в файл ".htaccess" для отдачи закэшированных страниц.
use Ideal\Core\Config;

$config = Config::getInstance();
$configCache = $config->cache;

$fileName = DOCUMENT_ROOT . '/.htaccess';

$file = file_get_contents($fileName);

if (!is_writable($fileName)) {
    echo 'Файл ' . $fileName . ' недоступен для записи';
    exit;
}

// Заменяем windows-переводы строки на unix-style
$file = mb_ereg_replace("\r\n", "\n", $file);

preg_match('/.*# file cache redirects(.*)# file cache redirects.*/is', $file, $matches);

if (count($matches) > 1) {
    $replaceText = <<<PHP
    
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
RewriteCond %{DOCUMENT_ROOT}/tmp/cache/fileCache/%{REQUEST_URI} -l
RewriteRule ^(.*)$ tmp/cache/fileCache/$1 [NC,L]

PHP;

    $file = str_replace($matches[1], $replaceText, $file);
    file_put_contents($fileName, $file);
}
