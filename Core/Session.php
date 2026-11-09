<?php

declare(strict_types=1);

namespace Ideal\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionPath = stream_resolve_include_path(DOCUMENT_ROOT . '/../tmp/sessions');
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0755, true);
            }
            ini_set('session.save_path', $sessionPath);
            session_start();
        }
    }
}