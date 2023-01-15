<?php

namespace src\components\telegram;

use src\config\Logger;
use Throwable;

class Adaptor
{
    const TELEGRAM_URL = 'https://api.telegram.org/bot';

    # Requests
    const ACTION_ANSWER_CALLBACK = 'answerCallbackQuery';
    const ACTION_SEND_MESSAGE    = 'sendMessage';

    # Request params
    const PARAM_CHAT_ID          = 'chat_id';
    const PARAM_REPLY_MARKUP     = 'reply_markup';
    const CALLBACK_QUERY_ID      = 'callback_query_id';
    const PARAM_TEXT             = 'text';
    const PARAM_SHOW_ALERT       = 'show_alert';
    const PARAM_INLINE_KEYBOARD  = 'inline_keyboard';
    const PARAM_CALLBACK_DATA    = 'callback_data';


    public function sendMessage(int $chatId, string $text, array $markupParams = []): bool
    {
        $params = [
            self::PARAM_CHAT_ID => $chatId,
            self::PARAM_TEXT    => $text,
        ];

        if (!empty($markupParams))
        {
            $markup = array_map(fn($key, $value) => [ self::PARAM_TEXT => $key, self::PARAM_CALLBACK_DATA => $value ] , array_keys($markupParams), $markupParams);
            $markupJson = json_encode([self::PARAM_INLINE_KEYBOARD => [$markup]]);

            $params[self::PARAM_REPLY_MARKUP] = $markupJson;
        }

        return $this->send(self::ACTION_SEND_MESSAGE, $params);
    }

    public function sendAnswerCallback(int $callbackId, string $text): bool
    {
        $params = [
            self::CALLBACK_QUERY_ID => $callbackId,
            self::PARAM_TEXT        => $text,
            self::PARAM_SHOW_ALERT  => 'true'
        ];

        return $this->send(self::ACTION_ANSWER_CALLBACK, $params);
    }

    public function send(string $action, array $params): bool
    {
        try {

            $url = self::TELEGRAM_URL . getenv('TELEGRAM_TOKEN') . '/' . $action;

            Logger::logToDB($url, Logger::TELEGRAM_SEND_MESSAGE_REQUEST);

            $ch = curl_init();

            $optArray = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true
            ];

            curl_setopt_array($ch, $optArray);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            $response = curl_exec($ch);
            $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($response && $status == 200)
            {
                Logger::logToDB($response, Logger::TELEGRAM_SEND_MESSAGE_RESPONSE);
                return $response['ok'];
            }
            else
            {
                Logger::logToDB($status . ' bad response', Logger::TELEGRAM_SEND_MESSAGE_RESPONSE);
                return false;
            }
        }
        catch (Throwable $exception)
        {
            Logger::logToDB('Error: ' . $exception->getMessage(), Logger::TELEGRAM_SEND_MESSAGE_RESPONSE);
            return false;
        }
    }
}