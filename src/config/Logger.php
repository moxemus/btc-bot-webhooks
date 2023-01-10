<?php

namespace src\config;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;

final class Logger
{
    const TELEGRAM_WEBHOOK_REQUEST       = 1;
    const TELEGRAM_SEND_MESSAGE_REQUEST  = 2;
    const TELEGRAM_SEND_MESSAGE_RESPONSE = 3;

    private static ?MonoLogger $monoLogger = null;

    private static function init(): void
    {
        if(!self::$monoLogger)
        {
            self::$monoLogger = new MonoLogger('main');
            self::$monoLogger->pushHandler(new StreamHandler(__DIR__ . '/runtime'));
        }
    }

    public static function logToFile(mixed $data): void
    {
        self::init();

        if (is_array($data))
        {
           $data = json_encode($data);
        }

        self::$monoLogger->info($data);
    }

    public static function logToDB(string $data, int $type): void
    {
        DB::exec("insert into logs (type, data) values($type, '$data')");
    }
}
