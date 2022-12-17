<?php

class BlockchainInfo extends Base
{
    protected string $url = 'https://blockchain.info/ticker';

    function getRate(): int
    {
        return 0;
    }
}