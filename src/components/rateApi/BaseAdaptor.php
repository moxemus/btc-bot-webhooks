<?php

namespace src\components\rateApi;

abstract class BaseAdaptor
{
    const BTC = 'btc';
    const ETH = 'eth';
    const DOGE = 'doge';

    protected string $url;
    abstract public function getRate(string $name): float;
}
