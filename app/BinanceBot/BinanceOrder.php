<?php

namespace App\BinanceBot;

class BinanceOrder
{
    private string $symbol;
    private int $orderId;
    private int $orderListId;
    private string $clientOrderId;
    private float $price;
    private float $origQty;
    private float $executedQty;
    private float $cummulativeQuoteQty;
    private string $status;
    private string $timeInForce;
    private string $type;
    private string $side;
    private float $stopPrice;
    private float $icebergQty;
    private int $time;
    private int $updateTime;
    private bool $isWorking;
    private float $origQuoteOrderQty;

    public function __construct(
        string $symbol,
        int $orderId,
        int $orderListId,
        string $clientOrderId,
        float $price,
        float $origQty,
        float $executedQty,
        float $cummulativeQuoteQty,
        string $status,
        string $timeInForce,
        string $type,
        string $side,
        float $stopPrice,
        float $icebergQty,
        int $time,
        int $updateTime,
        bool $isWorking,
        float $origQuoteOrderQty
    ) {
        $this->symbol = $symbol;
        $this->orderId = $orderId;
        $this->orderListId = $orderListId;
        $this->clientOrderId = $clientOrderId;
        $this->price = $price;
        $this->origQty = $origQty;
        $this->executedQty = $executedQty;
        $this->cummulativeQuoteQty = $cummulativeQuoteQty;
        $this->status = $status;
        $this->timeInForce = $timeInForce;
        $this->type = $type;
        $this->side = $side;
        $this->stopPrice = $stopPrice;
        $this->icebergQty = $icebergQty;
        $this->time = $time;
        $this->updateTime = $updateTime;
        $this->isWorking = $isWorking;
        $this->origQuoteOrderQty = $origQuoteOrderQty;
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getOrderListId(): int
    {
        return $this->orderListId;
    }

    /**
     * @return string
     */
    public function getClientOrderId(): string
    {
        return $this->clientOrderId;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getOrigQty(): float
    {
        return $this->origQty;
    }

    /**
     * @return float
     */
    public function getExecutedQty(): float
    {
        return $this->executedQty;
    }

    /**
     * @return float
     */
    public function getCummulativeQuoteQty(): float
    {
        return $this->cummulativeQuoteQty;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getTimeInForce(): string
    {
        return $this->timeInForce;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getSide(): string
    {
        return $this->side;
    }

    public function getStopPrice(): float
    {
        return $this->stopPrice;
    }

    public function getIcebergQty(): float
    {
        return $this->icebergQty;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getUpdateTime(): int
    {
        return $this->updateTime;
    }

    public function isWorking(): bool
    {
        return $this->isWorking;
    }

    public function getOrigQuoteOrderQty(): float
    {
        return $this->origQuoteOrderQty;
    }



}
