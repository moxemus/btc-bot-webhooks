<?php

namespace src\config;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonoLogger;
use src\components\telegram\Response as TelegramResponse;

final class Logger
{
    const TELEGRAM_WEBHOOK_REQUEST          = 1;
    const TELEGRAM_SEND_MESSAGE_REQUEST     = 2;
    const TELEGRAM_SEND_MESSAGE_RESPONSE    = 3;
    const TELEGRAM_WEBHOOK_REQUEST_DETAILED = 4;

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

    public static function logTelegramResponse(TelegramResponse $response)
    {
        $text = $response->id . ' ' . $response->userInfo['first_name'] . ': ' . $response->text;
        $type = self::TELEGRAM_WEBHOOK_REQUEST_DETAILED;

        DB::exec("insert into logs (type, data) values($type, '$text')");
    }
}
