<?php

namespace src\components\rateApi;

class BlockchainAdaptor extends BaseAdaptor
{
    protected string $url = 'https://blockchain.info/ticker';

    function getRate(): int
    {
        $info = file_get_contents($this->url);
        $info = json_decode($info, true);

        $rate = $info['USD']['last'] ?? 0;

        return (int)$rate;
    }
}