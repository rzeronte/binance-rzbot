<?php

namespace App\Console\Commands;

use App\BinanceBot\BinanceBot;
use App\BinanceBot\BinanceCandleTick;
use App\BinanceBot\BinanceCoinMyHistoryOperation;
use App\BinanceBot\BinanceOrder;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class BinanceBotCommand extends Command
{
    protected $signature = 'binance:bot {coin}';

    protected $description = 'Binance Bot Tool';

    private BinanceBot $api;

    private const REAL_TIME_WAIT_SECONDS = 2;
    private const BINANCE_MAX_LIMIT = 1000;

    public function __construct()
    {
        parent::__construct();

        $this->api = new BinanceBot(config('binance-rzbot'));
    }

    public function handle()
    {
        $coin = $this->argument("coin");

        $this->value($coin);
        $this->orders($coin);
        $this->history($coin);
        $this->depth($coin);
        $this->openOrders();
        $this->realTime($coin);
        $this->analyze($coin);
    }

    public function value(string $coin): void
    {
        if (!$this->api->isFeatureActive('value')) {
            return;
        }

        $this->info(sprintf("Value for %s: %s", $coin, $this->api->price($coin)));
        $this->info(sprintf("Value 24h ago for %s: %s", $coin, $this->api->prevDayPrice($coin)->getPriceChangePercent()));
    }

    public function orders(string $coin): void
    {
        if (!$this->api->isFeatureActive('orders')) {
            return;
        }

        $orders = $this->api->orders($coin);
        foreach ($orders as $order) {
            /* @var $order BinanceOrder */
            $this->info(sprintf("Order: %s", $order->getSymbol()));
        }
    }

    public function history(string $coin): void
    {
        if (!$this->api->isFeatureActive('history')) {
            return;
        }

        $operations = $this->api->historyOperations($coin);
        foreach ($operations as $operation) {
            /* @var $operation BinanceCoinMyHistoryOperation */
            $this->info(sprintf("Operation DONE: %s", $operation->getSymbol()));
        }
    }

    public function depth(string $coin): void
    {
        if (!$this->api->isFeatureActive('depth')) {
            return;
        }

        $depth = $this->api->depth($coin);
        $this->info(sprintf("DEPTH: Bids(%s) / Asks(%s)", $depth->sumBids(), $depth->sumAsks()));
    }

    public function openOrders(): void
    {
        if (!$this->api->isFeatureActive('openOrders')) {
            return;
        }

        $openOrders = $this->api->openOrders();
        foreach ($openOrders as $preOrder) {
            /* @var $preOrder BinanceOrder */
            $this->info(sprintf("OPEN Order: %s", $preOrder->getSymbol()));
        }
    }

    private function realTime(string $coin)
    {
        if (!$this->api->isFeatureActive('realTime')) {
            return;
        }

        $this->info("Starting realTime");

        while (true) {
            $output = "";
            $candleTicks = $this->api->candleTicks(
                $coin,
                $this->api->getOption('realTime','realTimeCandleTickInterval'),
                strtotime("-" . $this->api->getOption('realTime','realTimeMinutesAgo') . " minutes"),
                time()
            );

            if ($this->api->getOption('realTime','realTimeMinMaxPercentVerbose')) {
                $output.= $this->api->searchMinMaxPercentChangeWarnings(
                    $candleTicks,
                    $this->api->getOption('realTime', 'realTimeMinMaxPercentChangeWarning')
                );
            }

            if ($this->api->getOption('realTime',['realTimeLastTickPercentVerbose'])) {
                $output.= $this->api->searchLastPreviousPercentChangeWarnings(
                    $coin,
                    $candleTicks,
                    $this->api->getOption('realTime','realTimeLastTickPercentChangeWarning'),
                    $this->api->getOption('realTime','realTimeLastTickProfitPercentage'),
                    $this->api->getOption('realTime','realTimeBinanceCommissionForTrading'),
                    $this->api->getOption('realTime','realTimeTendenceNeededForBuy')
                );
            }

            $this->line(sprintf("[%s] %s %s",
                $this->api->getCurrentTime(),
                $coin,
                $output
            ));

            sleep(self::REAL_TIME_WAIT_SECONDS);
        }
    }

    private function analyze(string $coin)
    {
        if (!$this->api->isFeatureActive('analyze')) {
            return;
        }

        $this->info("Starting analyze");

        $tsFrom = strtotime($this->api->getOption('analyze','analyzeStartDate'));
        $tsTo = strtotime($this->api->getOption('analyze','analyzeEndDate'));

        $fromDatetime = new DateTime(date("Y-m-d H:i:s",$tsFrom));
        $toDateTime = new DateTime(date("Y-m-d H:i:s", $tsTo));

        $datesDiff = $toDateTime->diff($fromDatetime);

        $minutes = $datesDiff->days * 24 * 60;
        $minutes += $datesDiff->h * 60;
        $minutes += $datesDiff->i;

        if ($minutes > self::BINANCE_MAX_LIMIT) {
            $this->error("Range max reached (1000)");
            exit(-1);
        }

        $this->info(sprintf("Analyzing from %s to %s (minutes: %s)",
            date("Y-m-d H:i:s", $tsFrom),
            date("Y-m-d H:i:s", $tsTo),
            $minutes
        ));

        $this->separator();

        $this->api->candleTicks(
            $coin,
            $this->api->getOption('analyze','analyzeCandleTickInterval'),
            $tsFrom,
            $tsTo
        );

        $tsCurrentEnd = $tsFrom + 60 + 60;

        while ($tsCurrentEnd <= $tsTo+60) {
            $output = "";

            $minMaxStartIn = strtotime(
                sprintf("-%s minutes", $this->api->getOption('analyze','analyzeMinMaxBackRangeInMinutes')),
                $tsCurrentEnd
            );

            $candleMinMax = $this->api->candleTicksCache($minMaxStartIn, $tsCurrentEnd);
            if ($this->api->getOption('analyze', 'analyzeMinMaxPercentVerbose')) {
                $output.= $this->api->searchMinMaxPercentChangeWarnings(
                    $candleMinMax,
                    $this->api->getOption('analyze','analyzeMinMaxPercentChange')
                );
            }

            $candleTicksLastPreviousTick = $this->api->candleTicksCache($tsFrom, $tsCurrentEnd);
            if ($this->api->getOption('analyze', 'analyzeLastTickPercentVerbose')) {
                $output.= $this->api->searchLastPreviousPercentChangeWarnings(
                    $coin,
                    $candleTicksLastPreviousTick,
                    $this->api->getOption('analyze','analyzeLastTickPercentChange'),
                    $this->api->getOption('analyze','analyzeProfitPercentage'),
                    $this->api->getOption('analyze','analyzeBinanceCommissionForTrading'),
                    $this->api->getOption('analyze','analyzeTendenceNeededForBuy')
                );
            }

            $this->line(sprintf("[%s] %s", $this->api->getCurrentTime(), $output));
            $tsCurrentEnd += 60;

            usleep($this->api->getOption('analyze','analyzeSleepTime') * 1000000);
        }

        $this->separator();

        $this->line(sprintf("Total profit: %s (%s sells)",
            $this->api->formatScientistToFloat($this->api->currentProfitSells(), 8),
            $this->api->sellsCounter()
        ));

    }

    private function separator()
    {
        $this->info(str_repeat( "=", 80));
    }
}
