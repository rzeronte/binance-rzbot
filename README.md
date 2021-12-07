Binance PHP Bot for auto-trading

## Artisan interface
```
binance:bot          Binance Bot Tool
binance:wallet       Show my wallet in Binance
```
## Bot Parameters
```
Description:
  Binance Bot Tool

Usage:
  binance:bot [options] [--] <coin>

Arguments:
  coin                                                                               

Options:
      --value                                                                        Retrieve current value for coin
      --depth                                                                        Retrieve depth for coin
      --openOrders                                                                   Retrieve openOrders for coin
      --candleTicks                                                                  Retrieve candleTicks for coin
      --orders                                                                       Retrieve orders for coin
      --history                                                                      Retrieve history for coin
      --analyze                                                                      Analyze data in a interval of candleTicks
      --analyzeStartDate[=ANALYZESTARTDATE]                                          Analyze start date
      --analyzeEndDate[=ANALYZEENDDATE]                                              Analyze end date
      --analyzeMinMaxPercentChange[=ANALYZEMINMAXPERCENTCHANGE]                      Percent change warning for analyze in MinMax
      --analyzeLastTickPercentChange[=ANALYZELASTTICKPERCENTCHANGE]                  LastPercent change warning for analyze in LasTick
      --analyzeCandleTickInterval[=ANALYZECANDLETICKINTERVAL]                        Interval data to retrieve in candles (5m, 15m, 4h...)
      --analyzeMinMaxPercentVerbose[=ANALYZEMINMAXPERCENTVERBOSE]                    Verbose mode for MinMaxPercent data
      --analyzeLastTickPercentVerbose[=ANALYZELASTTICKPERCENTVERBOSE]                Verbose mode for LastTickPercent data
      --realTime                                                                     Active realTime mode
      --realTimeMinutesAgo[=REALTIMEMINUTESAGO]                                      Minutes ago for starTime parameter in realTime mode
      --realTimeMinMaxPercentVerbose[=REALTIMEMINMAXPERCENTVERBOSE]                  Verbose mode for MinMaxPercent data
      --realTimeLastTickPercentVerbose[=REALTIMELASTTICKPERCENTVERBOSE]              Verbose mode for LastTickPercent data
      --realTimeMinMaxPercentChangeWarning[=REALTIMEMINMAXPERCENTCHANGEWARNING]      Percent of change for warnings in realTime mode
      --realTimeCandleTickInterval[=REALTIMECANDLETICKINTERVAL]                      Interval data to retrieve in candles (5m, 15m, 4h...)
      --realTimeLastTickPercentChangeWarning[=REALTIMELASTTICKPERCENTCHANGEWARNING]  Percent change between last tick and previous
```
