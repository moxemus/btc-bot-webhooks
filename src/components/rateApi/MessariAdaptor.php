<?php

namespace src\components\rateApi;

class MessariAdaptor extends BaseAdaptor
{
    protected string $url = 'https://data.messari.io/api/v1/assets/{name}/metrics/market-data';

    /**
     * @param string $name
     * @return double
     */
    function getRate(string $name): float
    {
        $url = str_replace('{name}', $name, $this->url);
        $info = file_get_contents($url);
        $info = json_decode($info, true);

        $rate = $info['data']['market_data']['price_usd'] ?? 0;

        $scale = ($rate < 1) ? 4 : 0;
        return (float)bcdiv($rate, 1, $scale);
    }
}