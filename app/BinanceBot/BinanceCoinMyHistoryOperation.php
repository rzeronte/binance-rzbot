<?php

namespace App\BinanceBot;

class BinanceCoinMyHistoryOperation
{
    private string $symbol;
    private int $id;
    private int $orderId;
    private int $orderListId;
    private float $price;
    private float $qty;
    private float $quoteQty;
    private float $commission;
    private string $commissionAsset;
    private int $time;
    private bool $isBuyer;
    private string $isMaker;
    private bool $isBestMatch;

    public function __construct(
        string $symbol,
        int $id,
        int $orderId,
        int $orderListId,
        float $price,
        float $qty,
        float $quoteQty,
        float $commission,
        string $commissionAsset,
        int $time,
        bool $isBuyer,
        string $isMaker,
        bool $isBestMatch
    ) {
        $this->symbol = $symbol;
        $this->id = $id;
        $this->orderId = $orderId;
        $this->orderListId = $orderListId;
        $this->price = $price;
        $this->qty = $qty;
        $this->quoteQty = $quoteQty;
        $this->commission = $commission;
        $this->commissionAsset = $commissionAsset;
        $this->time = $time;
        $this->isBuyer = $isBuyer;
        $this->isMaker = $isMaker;
        $this->isBestMatch = $isBestMatch;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getOrderListId(): int
    {
        return $this->orderListId;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getQty(): float
    {
        return $this->qty;
    }

    public function getQuoteQty(): float
    {
        return $this->quoteQty;
    }

    public function getCommission(): float
    {
        return $this->commission;
    }

    public function getCommissionAsset(): string
    {
        return $this->commissionAsset;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function isBuyer(): bool
    {
        return $this->isBuyer;
    }

    public function getIsMaker(): string
    {
        return $this->isMaker;
    }

    public function isBestMatch(): bool
    {
        return $this->isBestMatch;
    }

}
