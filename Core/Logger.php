<?php

namespace Ideal\Core;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger
{
    /** @var array<MonologLogger> $instance */
    protected static array $instance;

    public static function getInstance(string $channel = 'main'): MonologLogger
    {
        if (isset(self::$instance[$channel]) && self::$instance[$channel] instanceof MonologLogger) {
            return self::$instance[$channel];
        }

        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true,
        );

        $log = new MonologLogger($channel);
        $log->pushHandler(
            (new StreamHandler(
                DOCUMENT_ROOT . '/../tmp/log/' . $_ENV['APP_ENV'] . '.log',
                MonologLogger::DEBUG,
            ))->setFormatter($formatter),
        );

        self::$instance[$channel] = $log;

        return $log;
    }
}
