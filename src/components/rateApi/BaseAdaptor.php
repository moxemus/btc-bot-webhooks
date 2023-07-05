<?php

namespace src\components\rateApi;

abstract class BaseAdaptor
{
    const BTC = 'btc';
    const BCH = 'bch';
    const ETH = 'eth';
    const DOGE = 'doge';
    const MATIC = 'matic';

    protected string $url;
    abstract public function getRate(string $name): float;
}
