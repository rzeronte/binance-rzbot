<?php
namespace App\BinanceBot;

class BinanceCandleTick
{
    private float $open;
    private float $high;
    private float $low;
    private float $close;
    private float $volume;
    private int $openTime;
    private int $closeTime;
    private float $assetVolume;
    private float $baseVolume;
    private int $trades;
    private float $assetBuyVolume;
    private float $takerBuyVolume;
    private bool $ignored;

    public function __construct(
        float $open,
        float $high,
        float $low,
        float $close,
        float $volume,
        int   $openTime,
        int   $closeTime,
        float $assetVolume,
        float $baseVolume,
        int   $trades,
        float $assetBuyVolume,
        float $takerBuyVolume,
        bool  $ignored
    )
    {
        $this->open = $open;
        $this->high = $high;
        $this->low = $low;
        $this->close = $close;
        $this->volume = $volume;
        $this->openTime = $openTime;
        $this->closeTime = $closeTime;
        $this->assetVolume = $assetVolume;
        $this->baseVolume = $baseVolume;
        $this->trades = $trades;
        $this->assetBuyVolume = $assetBuyVolume;
        $this->takerBuyVolume = $takerBuyVolume;
        $this->ignored = $ignored;
    }

    public function getOpen(): float
    {
        return $this->open;
    }

    public function getHigh(): float
    {
        return $this->high;
    }

    public function getLow(): float
    {
        return $this->low;
    }

    public function getClose(): float
    {
        return $this->close;
    }

    public function getVolume(): float
    {
        return $this->volume;
    }

    public function getOpenTime(): int
    {
        return $this->openTime;
    }

    public function getCloseTime(): int
    {
        return $this->closeTime;
    }

    public function getAssetVolume(): float
    {
        return $this->assetVolume;
    }

    public function getBaseVolume(): float
    {
        return $this->baseVolume;
    }

    public function getTrades(): int
    {
        return $this->trades;
    }

    public function getAssetBuyVolume(): float
    {
        return $this->assetBuyVolume;
    }

    public function getTakerBuyVolume(): float
    {
        return $this->takerBuyVolume;
    }

    public function isIgnored(): bool
    {
        return $this->ignored;
    }

}
