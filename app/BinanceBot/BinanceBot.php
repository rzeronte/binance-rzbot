<?php

namespace App\BinanceBot;

use Binance\API;
use Illuminate\Support\Facades\Log;
use Throwable;

class BinanceBot
{
    private API $api;
    private array $prices;
    private array $bookPrices;
    private BinanceAutomaticOrder $automaticOrder;

    private const LIMIT_CANDLES = 500;

    public function __construct()
    {
        $this->api = new API(
            env('BINANCE_APP_KEY'),
            env('BINANCE_APP_SECRET')
        );

        $this->automaticOrder = new BinanceAutomaticOrder();

        try {
            $this->prices = $this->api->prices();
            $this->bookPrices = $this->api->bookPrices();
        } catch (Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function candleTicks(
        string $coin,
        string $interval,
        int    $from,
        int    $to
    ): array
    {
        $candleTicks = $this->api->candlesticks(
            $coin,
            $interval,
            self::LIMIT_CANDLES,
            $this->formatTimestampMicroseconds($from),
            $this->formatTimestampMicroseconds($to)
        );

        $ticks = [];
        foreach ($candleTicks as $tick) {
            $ticks[] = new BinanceCandleTick(
                $tick["open"],
                $tick["high"],
                $tick["low"],
                $tick["close"],
                $tick["volume"],
                $tick["openTime"],
                $tick["closeTime"],
                $tick["assetVolume"],
                $tick["baseVolume"],
                $tick["trades"],
                $tick["assetBuyVolume"],
                $tick["takerBuyVolume"],
                $tick["ignored"]
            );
        }

        return $ticks;
    }

    public function price(string $coin)
    {
        if (!key_exists($coin, $this->prices)) {
            return null;
        }

        return $this->prices[$coin];
    }

    public function balance(): array
    {
        $balance = [];
        foreach ($this->api->balances($this->prices) as $crypto => $data) {
            if ($data["available"] > 0) {
                $balance[] = BinanceCoinMarketValue::from(
                    $crypto,
                    floatval($data["available"]),
                    floatval($data["btcValue"])
                );
            }
        }

        return $balance;
    }

    public function historyOperations(string $coin)
    {
        $operations = [];
        foreach ($this->api->history($coin) as $operation) {
            $operations[] = new BinanceCoinMyHistoryOperation(
                $operation["symbol"],
                $operation["id"],
                $operation["orderId"],
                $operation["orderListId"],
                $operation["price"],
                $operation["qty"],
                $operation["quoteQty"],
                $operation["commission"],
                $operation["commissionAsset"],
                $operation["time"],
                $operation["isBuyer"],
                $operation["isMaker"],
                $operation["isBestMatch"]
            );
        }

        return $operations;
    }

    public function depth(string $coin): BinanceCoinDepth
    {
        $depth = $this->api->depth($coin);

        return new BinanceCoinDepth($depth["bids"], $depth["asks"]);
    }

    public function openOrders(): array
    {
        $preOrders = [];
        foreach ($this->api->openOrders() as $order) {
            $preOrders[] = new BinanceOrder(
                $order["symbol"],
                $order["orderId"],
                $order["orderListId"],
                $order["clientOrderId"],
                $order["price"],
                $order["origQty"],
                $order["executedQty"],
                $order["cummulativeQuoteQty"],
                $order["status"],
                $order["timeInForce"],
                $order["type"],
                $order["side"],
                $order["stopPrice"],
                $order["icebergQty"],
                $order["time"],
                $order["updateTime"],
                $order["isWorking"],
                $order["origQuoteOrderQty"],
            );
        }

        return $preOrders;
    }

    public function orders(string $coin): array
    {
        $orders = [];
        foreach ($this->api->orders($coin) as $order) {
            $orders[] = new BinanceOrder(
                $order["symbol"],
                $order["orderId"],
                $order["orderListId"],
                $order["clientOrderId"],
                $order["price"],
                $order["origQty"],
                $order["executedQty"],
                $order["cummulativeQuoteQty"],
                $order["status"],
                $order["timeInForce"],
                $order["type"],
                $order["side"],
                $order["stopPrice"],
                $order["icebergQty"],
                $order["time"],
                $order["updateTime"],
                $order["isWorking"],
                $order["origQuoteOrderQty"],
            );

        }
        return $orders;
    }

    public function bid(string $coin)
    {
        return $this->bookPrices[$coin]['bid'];

    }

    public function prevDayPrice(string $coin): BinanceCoinPreviousDayTick
    {
        $coinPreviousDay = $this->api->prevDay($coin);

        return new BinanceCoinPreviousDayTick(
            $coinPreviousDay["symbol"],
            $coinPreviousDay["priceChange"],
            $coinPreviousDay["priceChangePercent"],
            $coinPreviousDay["weightedAvgPrice"],
            $coinPreviousDay["prevClosePrice"],
            $coinPreviousDay["lastPrice"],
            $coinPreviousDay["lastQty"],
            $coinPreviousDay["bidPrice"],
            $coinPreviousDay["bidQty"],
            $coinPreviousDay["askPrice"],
            $coinPreviousDay["askQty"],
            $coinPreviousDay["openPrice"],
            $coinPreviousDay["highPrice"],
            $coinPreviousDay["lowPrice"],
            $coinPreviousDay["volume"],
            $coinPreviousDay["quoteVolume"],
            $coinPreviousDay["openTime"],
            $coinPreviousDay["closeTime"],
            $coinPreviousDay["firstId"],
            $coinPreviousDay["lastId"],
            $coinPreviousDay["count"],
        );
    }

    public function percentageChange(float $old, float $new): float
    {
        return number_format((1 - $old / $new) * 100, 4);
    }

    public function formatScientistToFloat(float $value, int $numberDecimals): string
    {
        return sprintf('%.'.$numberDecimals.'f', $value);
    }


    public function formatTimestampSeconds(float $tsMicroseconds): int
    {
        return $tsMicroseconds / 1000;
    }

    public function formatTimestampMicroseconds(float $tsSeconds): int
    {
        return $tsSeconds * 1000;
    }

    public function automaticOrder(): BinanceAutomaticOrder
    {
        return $this->automaticOrder;
    }
}
