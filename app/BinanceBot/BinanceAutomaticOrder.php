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
    private float $desiredProfit;

    private float $binanceCommissionBuy;

    public function __construct()
    {
        $this->waitingForBuy = true;
        $this->waitingForSell = false;
    }

    public function buy(
        string $coin,
        string $time,
        float  $value,
        string $amount,
        float  $desiredProfitPercent,
        float  $binanceCommissionPercent
    ) {

        $this->waitingForBuy = false;
        $this->waitingForSell = true;

        $this->coin = $coin;
        $this->time = $time;
        $this->buyValue = $value;
        $this->amount = $amount;
        $this->desiredProfit = ($value * $desiredProfitPercent) / 100;
        $this->binanceCommissionBuy = $this->binanceCommission($value, $binanceCommissionPercent);

        Log::channel('binance-operations')->info(sprintf("buy: %s %s %s %s (desiredProfit: %s)",
            $coin,
            $time,
            sprintf('%.8f', $value),
            $amount,
            sprintf('%.8f', $this->desiredProfit)
        ));
    }

    public function binanceCommission(float $totalOperation, float $binanceCommission): float
    {
        return $totalOperation / 100 * $binanceCommission;
    }

    public function sell(string $time, string $marketValue)
    {
        Log::channel('binance-operations')->info(sprintf("sell: %s %s for %s (buy in %s) at %s",
            $this->amount,
            $this->coin,
            $marketValue,
            sprintf('%.8f', $this->buyValue),
            date("Y-m-d H:i:s", $time)
        ));

        $this->waitingForBuy = true;
        $this->waitingForSell = false;

        return $this->desiredProfit;
    }

    public function canBuy(): bool
    {
        return $this->waitingForBuy;
    }

    public function canSell(): bool
    {
        return $this->waitingForSell;
    }

    public function isProfitable(float $newMarketValue, float $binanceCommision): bool
    {
        $binanceCommisionSell = $this->binanceCommission($newMarketValue, $binanceCommision);
        Log::channel('binance-debug')->debug(sprintf("isProfitable => buyValue: %s | desiredProfit: %s | commissionBuy: %s | commissionSell: %s",
            sprintf('%.8f', $this->buyValue),
            sprintf('%.8f', $this->desiredProfit),
            sprintf('%.8f', $this->binanceCommissionBuy),
            sprintf('%.8f', $binanceCommisionSell)
        ));

        return ($this->buyValue + $this->desiredProfit + $this->binanceCommissionBuy + $binanceCommisionSell) < $newMarketValue;
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
