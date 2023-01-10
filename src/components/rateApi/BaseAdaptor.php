<?php

namespace src\components\rateApi;

abstract class BaseAdaptor
{
    protected string $url;
    abstract function getRate(): int;
}
