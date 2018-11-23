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

    public static function bitmex($api = '', $apiSecret = '') {
        if (!self::$exchange) self::$exchange = new bitmex();
        self::$exchange->apiKey = 'WkrVX4BG6aj4Y1rVGfZfB9CG';
        self::$exchange->secret = 'IFnTQcesYzCy3c8Srs5ULB8qZGpnHAOBvrfOwmnsHDJLLsFi';
        //self::$exchange->apiKey = $api;
        //self::$exchange->secret = $apiSecret;
        return self::$exchange;
    }

    public static function checkBalance($api = 'sdf', $apiSecret = 'sdf'){

        try{
            $response = self::bitmex($api, $apiSecret)->fetchBalance()['BTC']['free'];
            return $response;
        }
        catch (\Exception $e){
            $var =  preg_replace('~^bitmex ~', '', $e->getMessage());
            $var = json_decode($var, 1);
            //return json_encode($var, true);
            return $var;
        }
    }

    public static function checkSmallOrderExecution(){

        Self::$exchange = new bitmex();

        Self::$exchange->apiKey = 'WkrVX4BG6aj4Y1rVGfZfB9CG';
        Self::$exchange->secret = 'IFnTQcesYzCy3c8Srs5ULB8qZGpnHAOBvrfOwmnsHDJLLsFi';

        try{
            $quantity = 100;
            $response = Self::$exchange->createMarketBuyOrder('BTC/USD', $quantity, []);
            Self::$exchange->createMarketSellOrder('BTC/USD', $quantity, []);
            return $response;
        }
        catch (\Exception $e){
            return $e->getMessage();
        }


        return true;

    }
}