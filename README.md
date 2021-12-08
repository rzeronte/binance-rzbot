Binance PHP Bot for auto-trading

## Artisan interface
```
binance:bot {coin}   Binance Bot Tool
binance:wallet       Show my wallet in Binance
```
Example:
```
$ binance:bot BTCUSDT
```

## Config options
You can change *binance-rzbot.php* file in Laravel's config folder:
```
<?php

return [
      // Retrieve current value for coin
     'value' => ['active' => false,  'options' => [] ],
     // Retrieve depth for coin
     'depth' => ['active' => false,  'options' => [] ],
     // Retrieve openOrders for coin
     'openOrders'=> ['active' => false,  'options' => [] ],
     // Retrieve orders for coin
     'orders'=> ['active' => false,  'options' => [] ],
     // Retrieve history for coin
     'history'=> ['active' => false,  'options' => [] ],
     // Analyze
     'analyze'=> [
         'active' => true,
         'options' => [
             'analyzeStartDate' => '2021-12-08 00:00:00',       // Analyze start date
             'analyzeEndDate' => '021-12-08 14:00:00',          // Analyze end date
             'analyzeMinMaxPercentChange' => 1,                 // Percent change warning for analyze in MinMax
             'analyzeLastTickPercentChange' => 0.5,             // LastPercent change warning for analyze in LasTick
             'analyzeCandleTickInterval' => '1m',               // Interval data to retrieve in candles (5m, 15m, 4h...)
             'analyzeMinMaxPercentVerbose' => true,             // Verbose mode for MinMaxPercent data
             'analyzeLastTickPercentVerbose' => true,           // Verbose mode for LastTickPercent data
             'analyzeProfitPercentage' => 0.05,                 // Profit in sells
             'analyzeSleepTime' => 0.1,
             'analyzeBinanceCommissionForTrading' => 0.07500    // Binance comision for trade operations
         ]
     ],
     // Active realTime mode
     'realTime' => [
         'active' => false,
         'options' => [
             'realTimeMinutesAgo' => '30',                    // Minutes ago for starTime parameter in realTime mode
             'realTimeMinMaxPercentVerbose' => true,          // Verbose mode for MinMaxPercent data
             'realTimeLastTickPercentVerbose' => true,        // Verbose mode for LastTickPercent data
             'realTimeMinMaxPercentChangeWarning' => 0.5,     // Percent of change for warnings in realTime mode
             'realTimeCandleTickInterval' => '1m',            // Interval data to retrieve in candles (5m, 15m, 4h...)
             'realTimeLastTickPercentChangeWarning' => 0.9,   // Percent change between last tick and previous
             'realTimeLastTickProfitPercentage' => 0.01,      // Profit
             'realTimeBinanceCommissionForTrading' => 0.07500 // Binance comision for trade operations
         ]
     ]
];
```
