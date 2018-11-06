<?php

namespace App\Console\Commands;

use ccxt\bitfinex;
use Illuminate\Console\Command;
use ccxt\bitmex;
use Mockery\Exception;
use App\Client;

class btmx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btmx:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //$exchange = new \ccxt\hitbtc2();
        //$exchange->apiKey = '';
        //$exchange->secret = '';
        //dump($exchange->fetchBalance()); // hitbtc2.php line 660
        //dump($exchange->fetch2("trading/balance", "")); // Works good

        //dd($exchange->urls);

        $exchange = new bitmex();
        //dump(array_keys($exchange->load_markets())); // ETH/USD BTC/USD
        dump($exchange->fetch_ticker('ETH/USD'));
        $exchange->apiKey = 'WkrVX4BG6aj4Y1rVGfZfB9CG';
        $exchange->secret = 'IFnTQcesYzCy3c8Srs5ULB8qZGpnHAOBvrfOwmnsHDJLLsFi';
        //dump($exchange->fetchBalance()['BTC']['free']); // BTC balance

        //$response = $exchange->createMarketBuyOrder('ETH/USD', 1, []);
        //dump($response);

        //$response = $exchange->createMarketSellOrder('ETH/USD', 1, []);

        //dump($exchange->privatePostPositionLeverage(array('symbol' => 'ETHUSD', 'leverage' => 10))); // privatePostPositionLeverage

        //echo $response['timestamp'] . "\n";
        //echo date("Y-m-d G:i:s", $response['timestamp'] / 1000);

    }

}
