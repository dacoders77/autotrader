<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 11/22/2018
 * Time: 2:56 PM
 */

namespace App\Classes;
use App\Events\AttrUpdateEvent;
use App\Symbol; // Link model
use ccxt\bitmex;
use Mockery\Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class Client.
 * Check api keys via balance access and execution permissions via small order execution.
 * Called from Client.php controller.
 * Used as an additional validation prior to client record creation on the DB.
 * @package App\Classes
 */
class Client
{
    private static $exchange;
    private static $response;

    public static function bitmex($api = '', $apiSecret = '') {
        if (!self::$exchange) self::$exchange = new bitmex();
        self::$exchange->apiKey = $api;
        self::$exchange->secret = $apiSecret;
        return self::$exchange;
    }

    public static function checkBalance($api = '', $apiSecret = '', $request){
        try{
            if ($request == 'checkBalance') self::$response = self::bitmex($api, $apiSecret)->fetchBalance()['BTC']['free'];
            if ($request == 'getTradingBalance') self::$response = self::bitmex($api, $apiSecret)->privateGetPosition();
            return self::$response;
        }
        catch (\Exception $e){
            $var =  preg_replace('~^bitmex ~', '', $e->getMessage()); // Get rid of bitmex word word at the beginning of the message
            return json_decode($var, 1);
        }
    }

    public static function dropBalance($api = '', $apiSecret = '', $direction, $symbol, $quantity, $id){
        try{
            if ($direction == 'long'){
                self::$response = self::bitmex($api, $apiSecret)
                    ->createMarketSellOrder(Symbol::where('leverage_name', $symbol)->value('execution_name'), $quantity, []);
            }
            if ($direction == 'short'){
                self::$response = self::bitmex($api, $apiSecret)
                    ->createMarketBuyOrder(Symbol::where('leverage_name', $symbol)->value('execution_name'), abs($quantity), []);
            }
        }
        catch (\Exception $e){
            throw (new Exception(json_encode($e->getMessage())));
        }

        /* Need to wait a bit. Exchange delays balance update. */
        sleep(3);

        \App\Client::where('id', $id)->update([
            'balance_symbols' => self::makeClientTradingBalanceString(self::checkBalance($api, $apiSecret, 'getTradingBalance'))
        ]);

        try{
            event(new AttrUpdateEvent(['clients' => \App\Client::paginate()])); // Received in Clients.vue
        }
        catch (\Exception $e){
            Log::info('Pusher error: ' . __FILE__ . ' ' . __LINE__ . ' ' . \App\Client::paginate());
            throw new Exception($e);
        }
    }

    public static function checkSmallOrderExecution($api = '', $apiSecret = '', $symbol = 'BTC/USD'){
        self::$exchange = new bitmex();
        try{
            $quantity = 1;
            $response = self::bitmex($api, $apiSecret)->createMarketBuyOrder($symbol, $quantity, []);
            self::bitmex($api, $apiSecret)->createMarketSellOrder($symbol, $quantity, []);
            return $response;
        }
        catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public static function setLeverageCheck($api = '', $apiSecret = '', $symbol){
        self::$exchange = new bitmex();
        try{
            $response = self::bitmex($api, $apiSecret)->privatePostPositionLeverage(array('symbol' => $symbol, 'leverage' => 1));
            return $response;
        }
        catch (\Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * Get client trading balance symbols and make a string out of it.
     * Then insert this string to DB.
     * Value is outputted in Clients.vue.
     * Used in two cases: get client balance and drop client balance.
     *
     * @param array $response
     * @return string
     */
    public static function makeClientTradingBalanceString($response){
        $arr = "";
        foreach ($response as $symbol){
            if ($symbol['currentQty'] != 0)$arr .= $symbol['symbol'] . ":" . $symbol['currentQty'] . ", ";
        }
        return $arr;
    }
}