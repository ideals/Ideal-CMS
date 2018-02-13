<?php
header('Content-Type: text/html; charset=utf-8');

require __DIR__ . '/FileMonitor.php';

$settings = array(
    'scanDir' => __DIR__ . '/../../../..',
    'tmpDir' => __DIR__ . '/../../../../tmp',
    'scriptTime' => 50,
    'from' => 'filemonitor@example.com',
    'to' => 'user@example.com',
    'domain' => 'example.com',
    'exclude' => '',
);

// Запускаем мониторинг файлов
$files = new \FileMonitor\FileMonitor($settings);
$files->scan();
