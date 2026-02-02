<?php

namespace App\Core;

class Logger
{
    public static function log(string $message, string $level = 'info')
    {
        $date = date('Y-m-d H:i:s');
        $logFile = __DIR__ . '/../../storage/logs/app.log';
        $formattedMessage = "[$date] [$level] $message" . PHP_EOL;
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }

    public static function error(string $message)
    {
        self::log($message, 'error');
    }
}
