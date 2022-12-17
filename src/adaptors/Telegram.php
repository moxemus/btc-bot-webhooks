<?php

namespace src\adaptors;

use Throwable;

class Telegram
{
    protected string $url   = 'https://api.telegram.org/bot';
    protected string $token = '';

    public function sendMessage(int $chatId, string $text): void
    {
        try {
            $fullUrl = $this->url . $this->token. "/sendMessage?chat_id=" . $chatId
                . "&text=" . urlencode($text);
            $ch = curl_init();

            $optArray = [
                CURLOPT_URL            => $fullUrl,
                CURLOPT_RETURNTRANSFER => true
            ];

            curl_setopt_array($ch, $optArray);
            curl_exec($ch);
            curl_close($ch);

        } catch (Throwable $exception) {
        }
    }
}
