<?php

namespace src\adaptors;

use Throwable;

class Telegram
{
    protected string $url   = 'https://api.telegram.org/bot';
    protected string $token = '';

    public function sendMessage(int $chatId, string $text): bool
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
            $response = curl_exec($ch);
            curl_close($ch);

            // TODO : Add checking body for handling result
            // Then, after your curl_exec call:
//            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
//            $jsonBody    = substr($response, $header_size);
//            return $jsonBody['ok'] === true;

        } catch (Throwable $exception) {
            return false;
        }

        return true;
    }
}