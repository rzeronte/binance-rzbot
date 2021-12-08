<?php

namespace App\BinanceBot;

use Binance\API;
use Exception;
use Throwable;

class BinanceBot
{
    private array $config;


    private API $api;
    private array $prices;
    private array $bookPrices;

    private BinanceAutomaticOrder $automaticOrder;
    private array $candleTicksCache;
    private bool  $currentTendence;
    private float $currentProfitSells;
    private int $sellsCounter;

    private const LIMIT_CANDLES = 1000;

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->api = new API(
            env('BINANCE_APP_KEY'),
            env('BINANCE_APP_SECRET')
        );

        $this->automaticOrder = new BinanceAutomaticOrder();
        $this->currentTendence = 0;
        $this->currentProfitSells = 0;
        $this->sellsCounter = 0;

        try {
            $this->prices = $this->api->prices();
            $this->bookPrices = $this->api->bookPrices();
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
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
        $this->candleTicksCache = $ticks;

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

    public function candleTicksCache(string $tsFrom, string $tsTo): array
    {
        $tsFrom = $this->formatTimestampMicroseconds($tsFrom);
        $tsTo = $this->formatTimestampMicroseconds($tsTo);

        $ticks = [];
        foreach($this->candleTicksCache as $tick) {
            /* @var $tick BinanceCandleTick */
            if ($tick->getOpenTime() >= $tsFrom && $tick->getCloseTime() <= $tsTo) {
                $ticks[] = $tick;
            }
        }

        return $ticks;
    }

    public function config(string $key): ?array
    {
        return $this->config[$key] ?? null;
    }

    public function searchMinMaxPercentChangeWarnings(
        array $historyCandleTicks,
        float $percentChangeWarning
    ): string {
        $minValue = PHP_FLOAT_MAX;
        $maxValue = 0;

        $minTime = null;
        $maxTime = null;

        $minRangeDate = PHP_INT_MAX;
        foreach ($historyCandleTicks as $tick) {
            /* @var $tick BinanceCandleTick */
            $oldMax = $maxValue;
            $oldMin = $minValue;
            $maxValue = max($maxValue, $tick->getClose());
            $minValue = min($minValue, $tick->getClose());

            $minRangeDate = min($minRangeDate, $tick->getCloseTime());

            if ($oldMax !== $maxValue)  {
                $maxTime = $tick->getCloseTime();
                $maxTime = date('m-d H:i:s', $maxTime / 1000);
            }

            if ($oldMin !== $minValue)  {
                $minTime = $tick->getCloseTime();
                $minTime = date('m-d H:i:s', $minTime / 1000);
            }
        }

        $percentageChange = $this->percentageChange($minValue, $maxValue);

        $direction = null;
        if ($percentageChange < 0) {
            $direction = "<options=bold;fg=black;bg=red> NEGATIVE </>";
            $this->currentTendence = 0;
        } elseif ($percentageChange > 0) {
            $direction = "<options=bold;fg=black;bg=green> POSSITIVE </>";
            $this->currentTendence = 1;
        }

        $msg = sprintf("Min/Max[%s/%s] - [%s/%s]: Change: %s - %s",
            $minTime,
            $maxTime,
            $this->formatScientistToFloat($minValue, 8),
            $this->formatScientistToFloat($maxValue, 8),
            $percentageChange."%",
            $direction
        );

        if (abs($percentageChange) > $percentChangeWarning) {
            $msg = sprintf("<fg=blue>%s</>", $msg);
        } else {
            $msg = sprintf("<fg=white>%s</>", $msg);
        }

        return $msg;
    }

    public function searchLastPreviousPercentChangeWarnings(
        string $coin,
        array $historyCandleTicks,
        float $percentageChangeWarning,
        float $profitPercentForSell,
        float $binanceCommisionForTrading
    ): string
    {
        /* @var $lasTick BinanceCandleTick */
        $lasTick = end($historyCandleTicks);
        /* @var $previousTick BinanceCandleTick */

        $previousTick = array_slice($historyCandleTicks, -2, 1)[0];

        $percentageChange = $this->percentageChange($lasTick->getClose(), $previousTick->getClose());

        $direction = null;
        $directionText = "<options=bold;fg=black;bg=gray> EQUAL </>";
        if ($percentageChange < 0) {
            $direction = 1;
            $directionText = "<options=bold;fg=black;bg=green> UP </>";
        } elseif ($percentageChange > 0) {
            $direction = 0;
            $directionText = "<options=bold;fg=black;bg=red> DOWN </>";
        }

        $msg = sprintf(" | Prev [%s]: %s / Last [%s]: %s = %s %s",
            date('Y-m-d H:i:s', $this->formatTimestampSeconds($previousTick->getCloseTime())),
            $this->formatScientistToFloat($lasTick->getClose(), 8),
            date('Y-m-d H:i:s', $this->formatTimestampSeconds($lasTick->getCloseTime())),
            $this->formatScientistToFloat($previousTick->getClose(), 8),
            str_pad($percentageChange . "%", 10),
            $directionText,
        );

        if (abs($percentageChange) > $percentageChangeWarning) {
            if (!$direction && $this->automaticOrder()->canBuy()) {
                $this->automaticOrder()->buy(
                    $coin,
                    time(),
                    $this->formatScientistToFloat($previousTick->getClose(), 8),
                    1,
                    $profitPercentForSell,
                    $binanceCommisionForTrading
                );

                $msg .=" ====> BUY";
            }
            $msg = sprintf("<fg=magenta>%s</>", $msg);
        } else {
            $msg = sprintf("<fg=white>%s</>", $msg);
        }

        if ($this->automaticOrder()->canSell() ) {
            if ($this->currentTendence) {
                if ($this->automaticOrder()->isProfitable($previousTick->getClose(), $binanceCommisionForTrading)) {
                    $this->currentProfitSells += $this->automaticOrder()->sell(
                        time(),
                        $this->formatScientistToFloat($previousTick->getClose(), 8)
                    );
                    $this->sellsCounter++;
                    $msg .=" ====> SELL";
                }
            }
        }

        return $msg;
    }

    public function getCurrentTime(): string
    {
        return date('Y-m-d H:i:s', time());
    }

    public function currentProfitSells(): float
    {
        return $this->currentProfitSells;
    }

    public function sellsCounter(): int
    {
        return $this->sellsCounter;
    }

}
