<?php

abstract class Base
{
    protected string $url;

    abstract function getRate(): int;
}
