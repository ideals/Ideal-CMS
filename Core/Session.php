<?php

declare(strict_types=1);

namespace Ideal\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionPath = DOCUMENT_ROOT . '/../tmp/sessions';
            /** @noinspection MkdirRaceConditionInspection */
            if (!is_dir($sessionPath) && !mkdir($sessionPath, 0o755, true)) {
                throw new \RuntimeException(
                    sprintf('Directory "%s" was not created', $sessionPath),
                );
            }

            ini_set('session.save_path', $sessionPath);
            session_start();
        }
    }
}
