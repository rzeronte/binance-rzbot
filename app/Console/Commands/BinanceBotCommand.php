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
        $config = $this->api->config('value');

        if ($config['active']) {
            $this->info(sprintf("Value for %s: %s", $coin, $this->api->price($coin)));
            $this->info(sprintf("Value 24h ago for %s: %s", $coin, $this->api->prevDayPrice($coin)->getPriceChangePercent()));
        }
    }

    public function orders(string $coin): void
    {
        $config = $this->api->config('orders');

        if ($config['active']) {
            $orders = $this->api->orders($coin);
            foreach ($orders as $order) {
                /* @var $order BinanceOrder */
                $this->info(sprintf("Order: %s", $order->getSymbol()));
            }
        }
    }

    public function history(string $coin): void
    {
        $config = $this->api->config('history');

        if ($config['active']) {
            $operations = $this->api->historyOperations($coin);
            foreach ($operations as $operation) {
                /* @var $operation BinanceCoinMyHistoryOperation */
                $this->info(sprintf("Operation DONE: %s", $operation->getSymbol()));
            }
        }
    }

    public function depth(string $coin): void
    {
        $config = $this->api->config('depth');

        if ($config['active']) {
            $depth = $this->api->depth($coin);
            $this->info(sprintf("DEPTH: Bids(%s) / Asks(%s)", $depth->sumBids(), $depth->sumAsks()));
        }
    }

    public function openOrders(): void
    {
        $config = $this->api->config('openOrders');

        if ($config['active']) {
            $openOrders = $this->api->openOrders();
            foreach ($openOrders as $preOrder) {
                /* @var $preOrder BinanceOrder */
                $this->info(sprintf("OPEN Order: %s", $preOrder->getSymbol()));
            }
        }
    }

    private function realTime(string $coin)
    {
        $this->info("Starting realTime");

        $config = $this->api->config('realTime');

        if ($config['active']) {
            while (true) {
                $output = "";
                $candleTicks = $this->api->candleTicks(
                    $coin,
                    $config['options']['realTimeCandleTickInterval'],
                    strtotime("-" . $config['options']['realTimeMinutesAgo'] . " minutes"),
                    time()
                );

                if ($config['options']['realTimeMinMaxPercentVerbose']) {
                    $output.= $this->api->searchMinMaxPercentChangeWarnings(
                        $candleTicks,
                        $config['options']['realTimeMinMaxPercentChangeWarning']
                    );
                }

                if ($config['options']['realTimeLastTickPercentVerbose']) {
                    $output.= $this->api->searchLastPreviousPercentChangeWarnings(
                        $coin,
                        $candleTicks,
                        $config['options']['realTimeLastTickPercentChangeWarning'],
                        $config['options']['realTimeLastTickProfitPercentage'],
                        $config['options']['realTimeBinanceCommissionForTrading']
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
    }

    private function analyze(string $coin)
    {
        $this->info("Starting analyze");

        $config = $this->api->config('analyze');

        $tsFrom = strtotime($config['options']['analyzeStartDate']);
        $tsTo = strtotime($config['options']['analyzeEndDate']);

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
            $config['options']['analyzeCandleTickInterval'],
            $tsFrom,
            $tsTo
        );

        $tsCurrentEnd = $tsFrom + 60 + 60;

        while ($tsCurrentEnd <= $tsTo+60) {
            $output = "";
            $candleTicks = $this->api->candleTicksCache($tsFrom, $tsCurrentEnd);

            if ($config['options']['analyzeMinMaxPercentVerbose']) {
                $output.= $this->api->searchMinMaxPercentChangeWarnings(
                    $candleTicks,
                    $config['options']['analyzeMinMaxPercentChange']
                );
            }

            if ($config['options']['analyzeLastTickPercentVerbose']) {
                $output.= $this->api->searchLastPreviousPercentChangeWarnings(
                    $coin,
                    $candleTicks,
                    $config['options']['analyzeLastTickPercentChange'],
                    $config['options']['analyzeProfitPercentage'],
                    $config['options']['analyzeBinanceCommissionForTrading']
                );
            }

            $this->line(sprintf("[%s] %s", $this->api->getCurrentTime(), $output));
            $tsCurrentEnd += 60;

            usleep($config['options']['analyzeSleepTime'] * 1000000);
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
