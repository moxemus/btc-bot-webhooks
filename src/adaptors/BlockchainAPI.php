<?php

namespace src\adaptors;

class BlockchainAPI extends BaseRateAPI
{
    protected string $url = 'https://blockchain.info/ticker';

    function getRate(): int
    {
        return 0;
    }
}