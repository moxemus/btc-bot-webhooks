<?php

namespace src\components\telegram;

use src\components\rateApi\BaseAdaptor;
use src\components\rateApi\BlockchainAdaptor;
use src\config\DB;

class Handler
{
    const GREEN_SMILE = "\xE2\x9C\x85";
    const RED_SMILE   = "\xF0\x9F\x94\xBB";

    protected Adaptor     $telegramAdaptor;
    protected BaseAdaptor $apiAdaptor;
    protected DB          $db;

    public function __construct(?BaseAdaptor $apiAdaptor = null)
    {
        if (!$apiAdaptor)
        {
            $apiAdaptor = new BlockchainAdaptor();
        }

        $this->apiAdaptor = $apiAdaptor;
        $this->telegramAdaptor = new Adaptor();
        $this->db = new DB();
    }

    public function mail(): void
    {
        $users       = DB::query("SELECT id, is_admin from users");
        $currentRate = $this->apiAdaptor->getRate();

        foreach ($users as $user)
        {
            $lastRate = $user['last_rate'] ?? 0;
            $text     = $this->getRateMessage($currentRate, $lastRate);

            $this->telegramAdaptor->sendMessage($user['id'], $text);
            DB::exec("UPDATE users set last_rate = {$currentRate} where id = " . $user['id']);
        }
    }

    public function sendCurrentRate(int $chatId): bool
    {
        $currentRate = $this->apiAdaptor->getRate();
        $lastRate    = (int)DB::queryOne("select last_rate from users where id = {$chatId}")->last_rate;

        DB::exec("UPDATE users set last_rate = {$currentRate} where id = {$chatId}");

        return $this->sendMessage($chatId, $this->getRateMessage($currentRate, $lastRate));
    }

    public function sendAdminMenu(int $chatId): bool
    {
        $markupParams = [
            "Show users" => Response::COMMAND_USERS,
            "Show rate"  => Response::COMMAND_SHOW_RATE,
        ];

        return $this->sendMessage($chatId, 'Hello admin!', $markupParams);
    }

    public function sendUsers(?int $chatId): bool
    {
        if (is_null($chatId)) return false;

        $raw = DB::queryOne("select count(*) as cc from users");

        return $this->sendAnswerCallback($chatId, $raw->cc);
    }

    public function sendScheduleMenu(int $chatId): bool
    {
        $markupParams = [
            "Every day"             => Response::COMMAND_SCHEDULE_EVERY_DAY,
            "Every hour"            => Response::COMMAND_SCHEDULE_EVERY_HOUR,
            "Disable notifications" => Response::COMMAND_SCHEDULE_DISABLE
        ];

        return $this->sendMessage($chatId, 'Set up your notification schedule', $markupParams);
    }

    public function sendWelcome(int $chatId): bool
    {
        $this->sendMessage($chatId, 'Welcome to BTC rate bot!');
        return $this->sendCurrentRate($chatId);
    }

    public function sendAnswerCallback(int $callbackId, string $text): bool
    {
        return $this->telegramAdaptor->sendAnswerCallback($callbackId, $text);
    }

    protected function sendMessage(int $chatId, string $text, array $markupParams = []): bool
    {
        return $this->telegramAdaptor->sendMessage($chatId, $text, $markupParams);
    }

    protected function getRateMessage($currentRate, $lastRate): string
    {
        $smile   = ($currentRate >= $lastRate) ? self::GREEN_SMILE: self::RED_SMILE;
        $message = $currentRate . $smile;

        return $message;
    }
}