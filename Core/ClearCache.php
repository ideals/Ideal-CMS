<?php

namespace Ideal\Core;

class ClearCache
{
    public function execute(): void
    {
        $config = Config::getInstance();
        $configCache = $config->cache;

        // Очищаем файловый кэш
        if (isset($configCache['fileCache']) && $configCache['fileCache']) {
            FileCache::clearFileCache();
        }

        // Очищаем Memcache, только если он включён в настройках
        if ($config->cache['memcache']) {
            $memcache = Memcache::getInstance();
            $memcache->flush();
        }

        // Очищаем twig кэш
        View::clearTwigCache();

        // Удаляем сжатый css
        if (file_exists(DOCUMENT_ROOT . '/css/all.min.css')) {
            unlink(DOCUMENT_ROOT . '/css/all.min.css');
        }

        // Удаляем сжатый js
        if (file_exists(DOCUMENT_ROOT . '/js/all.min.js')) {
            unlink(DOCUMENT_ROOT . '/js/all.min.js');
        }
    }
}