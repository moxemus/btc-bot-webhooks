<?php

namespace src\components\telegram;

use Throwable;

class Adaptor
{
    const TELEGRAM_URL = 'https://api.telegram.org/bot';

    # Requests
    const ACTION_ANSWER_CALLBACK = 'answerCallbackQuery';
    const ACTION_SEND_MESSAGE = 'sendMessage';

    # Request params
    const PARAM_CHAT_ID = 'chat_id';
    const PARAM_REPLY_MARKUP = 'reply_markup';
    const CALLBACK_QUERY_ID = 'callback_query_id';
    const PARAM_TEXT = 'text';
    const PARAM_SHOW_ALERT = 'show_alert';
    const PARAM_INLINE_KEYBOARD = 'inline_keyboard';
    const PARAM_CALLBACK_DATA = 'callback_data';


    public function sendMessage(int $chatId, string $text, array $markupParams = []): bool
    {
        $params = [
            self::PARAM_CHAT_ID => $chatId,
            self::PARAM_TEXT => $text,
        ];

        if (!empty($markupParams)) {
            $markup = array_map(
                fn($key, $value) => [self::PARAM_TEXT => $key, self::PARAM_CALLBACK_DATA => $value],
                array_keys($markupParams),
                $markupParams
            );
            $markupJson = json_encode([self::PARAM_INLINE_KEYBOARD => [$markup]]);

            $params[self::PARAM_REPLY_MARKUP] = $markupJson;
        }

        return $this->send(self::ACTION_SEND_MESSAGE, $params);
    }

    public function sendAnswerCallback(int $callbackId, string $text): bool
    {
        $params = [
            self::CALLBACK_QUERY_ID => $callbackId,
            self::PARAM_TEXT => $text,
            self::PARAM_SHOW_ALERT => 'true'
        ];

        return $this->send(self::ACTION_ANSWER_CALLBACK, $params);
    }

    public function send(string $action, array $params): bool
    {
        try {

            $url = self::TELEGRAM_URL . getenv('TELEGRAM_TOKEN') . '/' . $action;

            $ch = curl_init();

            $optArray = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true
            ];

            curl_setopt_array($ch, $optArray);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            return ($response && $status == 200)
                ? $response['ok']
                : false;
        } catch (Throwable $exception) {
            return false;
        }
    }
}
