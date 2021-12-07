<?php

namespace App\Console\Commands;

use App\BinanceBot\BinanceBot;
use App\BinanceBot\BinanceCoinMarketValue;
use Illuminate\Console\Command;

class BinanceMyWalletCommand extends Command
{
    protected $signature = 'binance:wallet';

    protected $description = 'Show my wallet in Binance';

    private BinanceBot $api;

    public function __construct()
    {
        $this->api = new BinanceBot();
        parent::__construct();
    }

    public function handle()
    {
        $header = str_pad("CRYPTO" , 10);
        $header.= str_pad( "AVAILABLE", 20);
        $header.= str_pad( "VALUE", 20);
        $this->info($header);

        $this->separator();

        $total = 0;
        foreach ($this->api->balance() as $balance) {
            /* @var $balance BinanceCoinMarketValue */
            $msg = str_pad($balance->coin() , 10);
            $msg.= str_pad( $this->api->formatScientistToFloat($balance->amount(), 8), 15);
            $msg.= str_pad( $this->api->formatScientistToFloat($balance->value(), 8) . " BTC", 15);
            $this->info($msg);
            $total+=$balance->value();
        }
        $BTCinEUR =$this->api->price("BTCEUR");
        $euros = $total * $BTCinEUR;

        $euros = $this->api->formatScientistToFloat($euros, 2);
        $total = $this->api->formatScientistToFloat($total, 8);

        $this->separator();

        $this->info("Total: " . $total . "BTC / ". $euros . "€");
    }

    private function separator()
    {
        $this->info(str_repeat( "=", 15*3));
    }
}
