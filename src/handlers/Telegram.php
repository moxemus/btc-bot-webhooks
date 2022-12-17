<?php

namespace src\handlers;

use src\adaptors\BaseRateAPI;
use src\adaptors\Telegram as TelegramApiAdaptor;

class Telegram
{
    protected TelegramApiAdaptor $telegramAdaptor;
    protected BaseRateAPI        $apiAdaptor;

    public function __construct(BaseRateAPI $apiAdaptor)
    {
        $this->apiAdaptor = $apiAdaptor;
        $this->telegramAdaptor = new TelegramApiAdaptor();
    }

    public function mail(): void
    {

    }

    public function sendMessage(): void
    {

    }
}