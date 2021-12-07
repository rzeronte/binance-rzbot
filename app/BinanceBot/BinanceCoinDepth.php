<?php

namespace App\BinanceBot;

class BinanceCoinDepth
{
    private array $bids;
    private array $asks;

    public function __construct(array $bids, array $asks)
    {
        $this->bids = $bids;
        $this->asks = $asks;
    }

    public function sumBids()
    {
        $total = 0;
        foreach($this->bids as $bid) {
            $total += $bid;
        }

        return $total;
    }

    public function sumAsks()
    {
        $total = 0;
        foreach($this->asks as $ask) {
            $total += $ask;
        }

        return $total;
    }

}
