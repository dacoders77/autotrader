<?php

namespace App\Console\Commands;

use ccxt\bitfinex;
use ccxt\ExchangeError;
use Illuminate\Console\Command;
use ccxt\bitmex;
use Mockery\Exception;

use App\Client; // Link model
use App\Execution; // Link model
use App\Signal; // Link model

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
        $exchange->apiKey = 'WkrVX4BG6aj4Y1rVGfZfB9CG';
        $exchange->secret = 'IFnTQcesYzCy3c8Srs5ULB8qZGpnHAOBvrfOwmnsHDJLLsFi';

        //dump(array_keys($exchange->load_markets())); // ETH/USD BTC/USD

        try{
            //$r = $exchange->fetch_ticker('BTC/USD'); // BCHZ18 works good
            //$r = $exchange->privatePostPositionLeverage(array('symbol' => 'XBTUSD', 'leverage' => 10));
            //dump($r);
        }
        catch (ExchangeError $e)
        {
            //echo $e->getMessage();
        }

        //dump($exchange->fetchBalance()['BTC']['free']); // BTC balance

        //$response = $exchange->createMarketBuyOrder('BTC/USD', 1, []);
        //dump($response);

        //$response = $exchange->createMarketSellOrder('ETHUSD', 1, []);

        //dump($exchange->privatePostPositionLeverage(array('symbol' => 'ETHUSD', 'leverage' => 10))); // privatePostPositionLeverage ADAZ18


        $arr = Execution::where('signal_id', 21)->get(['in_place_order_status']);
        $push = array();
        foreach($arr as $object)
        {
            array_push($push, $object->{'in_place_order_status'});
        }
        dump(array_flip($push));
        dump(count(array_keys(array_flip($push))));

        if(count(array_keys(array_flip($push))) == 1){
            if (array_key_exists('ok', array_flip($push))){
                dump('signal status: ok');
            }
        }
        if(count(array_keys(array_flip($push))) > 1){
            if (array_key_exists('ok', array_flip($push))){
                dump('signal status: error');
            }
        }


        // Count = 1
        // Set button to stop
        // in_status == ok
        // Signal status = success

        // Count > 1
        // Status = error




    }

}
