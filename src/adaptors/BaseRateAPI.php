<?php

namespace src\adaptors;

abstract class BaseRateAPI
{
    protected string $url;
    abstract function getRate(): int;
}
