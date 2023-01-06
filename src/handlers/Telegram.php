<?php

namespace src\handlers;

use src\adaptors\BaseRateAPI;
use src\adaptors\BlockchainAPI;
use src\adaptors\Telegram as TelegramApiAdaptor;
use src\config\DB;

class Telegram
{
    const GREEN_SMILE = "\xE2\x9C\x85";
    const RED_SMILE   = "\xF0\x9F\x94\xBB";

    protected TelegramApiAdaptor $telegramAdaptor;
    protected BaseRateAPI $apiAdaptor;
    protected DB $db;

    public function __construct(?BaseRateAPI $apiAdaptor = null)
    {
        if (!$apiAdaptor)
        {
            $apiAdaptor = new BlockchainAPI();
        }

        $this->apiAdaptor = $apiAdaptor;
        $this->telegramAdaptor = new TelegramApiAdaptor();
        $this->db = new DB();
    }

    public function mail(): void
    {
        $users = DB::query("SELECT id, is_admin from users");

        if (empty($users)) return;

        $currentRate = $this->apiAdaptor->getRate();
        $lastRate    = DB::query("SELECT val from rates where id = 1");

        $smile       = ($currentRate >= $lastRate) ? self::GREEN_SMILE: self::RED_SMILE;
        $message     = $currentRate . $smile;

        foreach ($users as $user)
        {
            $text = ($user['is_admin'] == 1)
                ? $message . " " . count($users)
                : $message;

            $this->telegramAdaptor->sendMessage($user['id'], $text);
        }

        DB::exec("UPDATE rates set val = {$currentRate}");
    }

    public function sendCurrentRate(int $chatId): bool
    {
        return $this->sendMessage($chatId, $this->getRateMessage());
    }

    public function sendMessage(int $chatId, string $text): bool
    {
        return $this->telegramAdaptor->sendMessage($chatId, $text);
    }

    protected function getRateMessage(): bool
    {
        $currentRate = $this->apiAdaptor->getRate();
        $lastRate    = DB::query("SELECT val from rates where id = 1");

        $smile       = ($currentRate >= $lastRate) ? self::GREEN_SMILE: self::RED_SMILE;
        $message     = $currentRate . $smile;

        return $message;
    }
}