<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 12/5/2018
 * Time: 3:21 AM
 */

namespace App\Classes;

use App\Events\AttrUpdateEvent;
use App\Symbol; // Link model
use Illuminate\Support\Facades\Cache;

/**
 * Once a second check cache keys.
 * If subscribe or unsubscribed keys present in object -> prepare a json object and sent it to open
 * websocket connection.
 * Called from:
 * - btmxws.php - when console command is started.
 * - SymbolController.php - when symbol is created/deleted. When created we subscribe, deleted - unsubscribe.
 *
 * Class Websocket
 * @package App\Classes
 * @see  https://www.bitmex.com/app/wsAPI#API-Keys
 */
class Websocket
{
    private static $webSocketObject;

    /* Get all symbols name and push the to array. This list is the same for subscribe and unsubscribe methods. */
    public static function symbolsList()
    {
        $symbolList = array();
        foreach (Symbol::all() as $symbol){
            $str = "instrument:$symbol->leverage_name";
            array_push($symbolList, $str);
        }
        return $symbolList;
    }

    public static function listenCache($connection){
        echo "order obj: " . self::$webSocketObject . "\n";

        if (Cache::get('object'))
        {
            if(array_key_exists('subscribe', Cache::get('object'))){
                self::$webSocketObject = json_encode([
                    "op" => "subscribe",
                    //"args" => self::symbolsList()
                    "args" => Cache::get('object')['subscribe']
                ]);
            }
            if(array_key_exists('subscribeInit', Cache::get('object'))){
                self::$webSocketObject = json_encode([
                    "op" => "subscribe",
                    "args" => self::symbolsList()
                ]);
            }

            /**
             * Not used. For now we only subscribe.
             * Unsubscription disabled and not used.
             * @todo DELETE IT
             */
            if(array_key_exists('unsubscribe', Cache::get('object'))){
                self::$webSocketObject = json_encode([
                    "op" => "unsubscribe",
                    "args" => self::symbolsList()
                ]);
            }
        }
        if ($connection){
            if (self::$webSocketObject) {
                $connection->send(self::$webSocketObject);
            }
            else{
                dump('No connection. ' . __FILE__);
            }
            //echo "order obj: " . self::$webSocketObject . "\n";
        }

        Cache::put('object', null, now()->addMinute(5));
        self::$webSocketObject = null;
    }
}