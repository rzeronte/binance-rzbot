<?php

namespace App\BinanceBot;

class BinanceCoinMarketValue
{
    private string $coin;
    private float $amount;
    private float $value;

    private function __construct(string $coin, float $amount, float $value)
    {
        $this->coin = $coin;
        $this->amount = $amount;
        $this->value = $value;
    }

    static public function from(string $coin, float $amount, float $value): self
    {
        return new self($coin, $amount, $value);
    }

    public function coin(): string
    {
        return $this->coin;
    }

    public function value(): float
    {
        return $this->value;
    }

    public function amount(): float
    {
        return $this->amount;
    }

}
