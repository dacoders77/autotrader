<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 11/22/2018
 * Time: 2:56 PM
 */

namespace App\Classes;
use ccxt\bitmex;
use Mockery\Exception;
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

            //$response = self::bitmex($api, $apiSecret)->createMarketBuyOrder($symbol, $quantity, []);
            //self::bitmex($api, $apiSecret)->createMarketSellOrder($symbol, $quantity, []);
            return $response;
        }
        catch (\Exception $e){
            return $e->getMessage();
        }
    }
}