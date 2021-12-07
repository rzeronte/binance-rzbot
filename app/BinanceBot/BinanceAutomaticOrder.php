<?php

namespace App\BinanceBot;

use Illuminate\Support\Facades\Log;

final class BinanceAutomaticOrder
{
    private bool $waitingForBuy;
    private bool $waitingForSell;

    private string $coin;
    private string $time;
    private float $buyValue;
    private string $amount;

    public function __construct()
    {
        $this->waitingForBuy = true;
        $this->waitingForSell = false;
    }

    public function buy(string $coin, string $time, float $value, string $amount)
    {
        Log::debug(sprintf("buy: %s %s %s %s", $coin, $time, sprintf('%.8f', $value), $amount));

        $this->waitingForBuy = false;
        $this->waitingForSell = true;

        $this->coin = $coin;
        $this->time = $time;
        $this->buyValue = $value;
        $this->amount = $amount;
    }

    public function sell(string $time, string $marketValue)
    {
        Log::debug(sprintf("sell: %s %s for %s (buy in %s) at %s",
            $this->amount,
            $this->coin,
            $this->buyValue,
            $marketValue,
            date("Y-m-d H:i:s", $time)
        ));

        $this->waitingForBuy = true;
        $this->waitingForSell = false;
    }

    public function canBuy(): bool
    {
        return $this->waitingForBuy;
    }

    public function canSell(): bool
    {
        return $this->waitingForSell;
    }

    public function isProfitable(float $newMarketValue, float $desiredProfitPercent): bool
    {
        $desiredProfitValue = $this->percentage($desiredProfitPercent, $this->buyValue);

        return ($this->buyValue + $desiredProfitValue) < $newMarketValue;
    }

    public function buyValue(): float
    {
        return $this->buyValue;
    }

    public function percentage($percentage, $totalWidth): float
    {
        return ($percentage / 100) * $totalWidth;
    }
}
