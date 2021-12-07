<?php

namespace App\Console\Commands;

use App\BinanceBot\BinanceBot;
use App\BinanceBot\BinanceCandleTick;
use App\BinanceBot\BinanceCoinMyHistoryOperation;
use App\BinanceBot\BinanceOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class BinanceBotCommand extends Command
{
    protected $signature = 'binance:bot {coin}
         {--value : Retrieve current value for coin }
         {--depth : Retrieve depth for coin }
         {--openOrders : Retrieve openOrders for coin }
         {--candleTicks : Retrieve candleTicks for coin }
         {--orders : Retrieve orders for coin }
         {--history : Retrieve history for coin }

         {--analyze : Analyze data in a interval of candleTicks }
         {--analyzeStartDate= : Analyze start date }
         {--analyzeEndDate= : Analyze end date }
         {--analyzeMinMaxPercentChange= : Percent change warning for analyze in MinMax}
         {--analyzeLastTickPercentChange= : LastPercent change warning for analyze in LasTick }
         {--analyzeCandleTickInterval= : Interval data to retrieve in candles (5m, 15m, 4h...) }
         {--analyzeMinMaxPercentVerbose= : Verbose mode for MinMaxPercent data }
         {--analyzeLastTickPercentVerbose= : Verbose mode for LastTickPercent data }

         {--realTime : Active realTime mode }
         {--realTimeMinutesAgo= : Minutes ago for starTime parameter in realTime mode }
         {--realTimeMinMaxPercentVerbose= : Verbose mode for MinMaxPercent data }
         {--realTimeLastTickPercentVerbose= : Verbose mode for LastTickPercent data }
         {--realTimeMinMaxPercentChangeWarning= : Percent of change for warnings in realTime mode }
         {--realTimeCandleTickInterval= : Interval data to retrieve in candles (5m, 15m, 4h...) }
         {--realTimeLastTickPercentChangeWarning= : Percent change between last tick and previous }
    ';

    protected $description = 'Binance Bot Tool';

    private BinanceBot $api;
    private const REAL_TIME_WAIT_SECONDS = 4;
    private const DEFAULT_PERCENT_CHANGE_WARNING = 5;

    public function __construct()
    {
        $this->api = new BinanceBot();
        parent::__construct();
    }

    public function handle()
    {
        $coin = $this->argument("coin");

        $this->value($coin);
        $this->orders($coin);
        $this->history($coin);
        $this->depth($coin);
        $this->openOrders();
        $this->candleTicks($coin);
        $this->realTime($coin);
        $this->analyze($coin);
    }

    public function value(string $coin): void
    {
        if ($this->option("value")) {
            $this->info(sprintf("Value for %s: %s", $coin, $this->api->price($coin)));
            $this->info(sprintf("Value 24h ago for %s: %s", $coin, $this->api->prevDayPrice($coin)->getPriceChangePercent()));
        }
    }

    public function orders(string $coin): void
    {
        if ($this->option("orders")) {
            $orders = $this->api->orders($coin);
            foreach ($orders as $order) {
                /* @var $order BinanceOrder */
                $this->info(sprintf("Order: %s", $order->getSymbol()));
            }
        }
    }

    public function history(string $coin): void
    {
        if ($this->option("history")) {
            $operations = $this->historyOperations($coin);
            foreach ($operations as $operation) {
                /* @var $operation BinanceCoinMyHistoryOperation */
                $this->info(sprintf("Operation DONE: %s", $operation->getSymbol()));
            }
        }
    }

    public function depth(string $coin): void
    {
        if ($this->option("depth")) {
            $depth = $this->api->depth($coin);
            $this->info(sprintf("DEPTH: Bids(%s) / Asks(%s)", $depth->sumBids(), $depth->sumAsks()));
        }
    }

    public function openOrders(): void
    {
        if ($this->option("openOrders")) {
            $openOrders = $this->api->openOrders();
            foreach ($openOrders as $preOrder) {
                /* @var $preOrder BinanceOrder */
                $this->info(sprintf("OPEN Order: %s", $preOrder->getSymbol()));
            }
        }
    }

    public function candleTicks(string $coin): void
    {
        if ($this->option("candleTicks")) {
            $candleTicks = $this->api->candleTicks(
                $coin,
                strtotime($this->option('realTimeCandleTickInterval')),
                strtotime("-" .$this->option('realTimeMinutesAgo') . " minutes"),
                time()
            );
            foreach ($candleTicks as $tick) {
                /* @var $tick BinanceCandleTick */
                $this->info(sprintf("TICK: Open: %s, Close: %s",
                    $tick->getOpen(),
                    $tick->getClose()
                ));
            }
        }
    }

    private function realTime(string $coin)
    {
        if ($this->option("realTime")) {
            while (true) {
                $candleTicks = $this->api->candleTicks(
                    $coin,
                    $this->option('realTimeCandleTickInterval'),
                    strtotime("-" .$this->option('realTimeMinutesAgo') . " minutes"),
                    time()
                );

                if ($this->option('realTimeMinMaxPercentVerbose')) {
                    $this->searchMinMaxPercentChangeWarnings(
                        $coin,
                        $candleTicks,
                        $this->option('realTimeMinMaxPercentChangeWarning')
                    );
                }

                if ($this->option('realTimeLastTickPercentVerbose')) {
                    $this->searchLastPreviousPercentChangeWarnings(
                        $coin,
                        $candleTicks,
                        $this->option('realTimeLastTickPercentChangeWarning')
                    );
                }

                sleep(self::REAL_TIME_WAIT_SECONDS);
            }
        }
    }

    private function searchMinMaxPercentChangeWarnings(string $coin, array $historyCandleTicks, float $percentChangeWarning)
    {
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
                $maxTime = date('m-d h:i:s', $maxTime / 1000);
            }

            if ($oldMin !== $minValue)  {
                $minTime = $tick->getCloseTime();
                $minTime = date('m-d h:i:s', $minTime / 1000);
            }
        }

        $percentageChange = $this->api->percentageChange($minValue, $maxValue);

        $msg = sprintf("[%s] %s - Range Min[%s]: %s / Range Max[%s]: %s = %s%%",
            $this->getCurrentTime(),
            $coin,
            $minTime,
            $this->api->formatScientistToFloat($minValue, 8),
            $maxTime,
            $this->api->formatScientistToFloat($maxValue, 8),
            $percentageChange
        );

        if (abs($percentageChange) > $percentChangeWarning) {
            $this->warn($msg);
        } else {
            $this->info($msg);
        }
    }

    private function searchLastPreviousPercentChangeWarnings(string $coin, array $historyCandleTicks, float $percentageChangeWarning)
    {
        /* @var $lasTick BinanceCandleTick */
        $lasTick = end($historyCandleTicks);
        /* @var $previousTick BinanceCandleTick */
        $previousTick = array_slice($historyCandleTicks, -2, 1)[0];

        $percentageChange = $this->api->percentageChange($lasTick->getClose(), $previousTick->getClose());

        $direction = null;
        if ($percentageChange < 0) {
            $direction = "UP";
        } elseif ($percentageChange > 0) {
            $direction = "DOWN";
        }

        $msg = sprintf("[%s] %s - Last Tick [%s]: %s / Previous Tick [%s]: %s = %s%% %s",
            $this->getCurrentTime(),
            $coin,
            date('Y-m-d h:i:s', $this->api->formatTimestampSeconds($lasTick->getCloseTime())),
            $this->api->formatScientistToFloat($lasTick->getClose(), 8),
            date('Y-m-d h:i:s', $this->api->formatTimestampSeconds($previousTick->getCloseTime())),
            $this->api->formatScientistToFloat($previousTick->getClose(), 8),
            $percentageChange,
            $direction
        );

        if (abs($percentageChange) > $percentageChangeWarning) {
            $this->warn($msg);

            if ($direction === "DOWN" && $this->api->automaticOrder()->canBuy()) {
                $this->api->automaticOrder()->buy(
                    $coin,
                    time(),
                    $this->api->formatScientistToFloat($previousTick->getClose(), 8),
                    1
                );
            }

        } else {
            $this->info($msg);
        }

        if ($this->api->automaticOrder()->canSell()) {
            $desiredProfitPercent = 0.005;

            $profit = $this->api->formatScientistToFloat(
                $this->api->automaticOrder()->percentage(
                    $desiredProfitPercent,
                    $this->api->automaticOrder()->buyValue()
                ),8
            );

            if ($this->api->automaticOrder()->isProfitable($previousTick->getClose(), $desiredProfitPercent)) {
                $this->api->automaticOrder()->sell(
                    time(),
                    $this->api->formatScientistToFloat($previousTick->getClose(), 8)
                );
            }
        }
    }

    private function getCurrentTime(): string
    {
        return date('Y-m-d h:i:s', time());
    }

    private function analyze(string $coin)
    {
        $tsFrom = strtotime($this->option('analyzeStartDate'));
        $tsTo = strtotime($this->option('analyzeEndDate'));

        $this->info(sprintf("Analyzing from %s to %s",
            date("Y-m-d H:i:s", $tsFrom),
            date("Y-m-d H:i:s", $tsTo)
        ));

        $this->separator();

        $tsCurrentEnd = $tsFrom;

        while (true) {
            $this->info(sprintf("Simulating from %s to %s",
                date("Y-m-d H:i:s", $tsTo),
                date("Y-m-d H:i:s", $tsCurrentEnd)
            ));

            $candleTicks = $this->api->candleTicks(
                $coin,
                $this->option('analyzeCandleTickInterval'),
                $tsFrom,
                $tsCurrentEnd
            );

            if ($this->option('analyzeMinMaxPercentVerbose')) {
                $this->searchMinMaxPercentChangeWarnings(
                    $coin,
                    $candleTicks,
                    $this->option('analyzeMinMaxPercentChange', 1)
                );
            }

            if ($this->option('analyzeLastTickPercentVerbose')) {
                $this->searchLastPreviousPercentChangeWarnings(
                    $coin,
                    $candleTicks,
                    $this->option('analyzeLastTickPercentChange', 1)
                );
            }

            $tsCurrentEnd += 60;
            sleep(self::REAL_TIME_WAIT_SECONDS);
        }
    }

    private function separator()
    {
        $this->info(str_repeat( "=", 15*3));
    }

}
