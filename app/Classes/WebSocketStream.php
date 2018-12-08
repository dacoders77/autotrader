<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 12/4/2018
 * Time: 2:55 PM
 */

namespace App\Classes;

use App\Events\AttrUpdateEvent;
use App\Http\Controllers\API\ExecutionController;
use App\Signal; // Link model
use App\Symbol;

/**
 * Pars websocket messages.
 * On each message update it's value in symbols, signals table and send an event to symbols.vue and signals.vue via pusher.
 * @see https://pusher.com/
 *
 * Class WebSocketStream
 * @package App\Classes
 */
class WebSocketStream
{
    /* @var boolean Rate limit first enter flag. */
    private static $isFirstTimeTickCheck;
    private static $addedTickTime;

    public static function Parse(array $message){

        Symbol::where('leverage_name', $message[0]['symbol'])
            ->update([
                'quote_value' => $message[0]['lastPrice']
            ]);

        /* We don't have execution name in signals table, but we do in symbols */
        $executionSymbolName = Symbol::where('leverage_name', $message[0]['symbol'])->value('execution_name');

        Signal::where('symbol', $executionSymbolName)
            ->where('info', null) // Update price only the signal was not close with stop loss
            ->update([
                'quote_value' => $message[0]['lastPrice']
            ]);

        $quoteTickTime = strtotime($message[0]['timestamp']);

        /* Send event to vue.js once a second */
        if (self::$isFirstTimeTickCheck || $quoteTickTime >= self::$addedTickTime){
            self::$isFirstTimeTickCheck = false;
            self::$addedTickTime = $quoteTickTime + 1; // Allow ticks not frequenter than twice a second
            /* Event is received in signals.vue, symbols.vue */
            event(new AttrUpdateEvent(['signal' => Signal::paginate(), 'symbol' => Symbol::paginate(), 'ticker' => $message[0]['symbol'], 'price' => $message[0]['lastPrice']]));
        }
    }

    public  static function stopLossCheck($message){

        /* We don't have execution name in signals table, but we do in symbols */
        $executionSymbolName = Symbol::where('leverage_name', $message[0]['symbol'])->value('execution_name');
        // Run through this array

        //dump($message[0]['symbol'] . " " . $message[0]['lastPrice']);

        foreach (Signal::where('symbol', $executionSymbolName)->get() as $signal){
            if($signal->direction == "long" && $signal->info != "stop_loss"){
                if($message[0]['lastPrice'] < (double)$signal->stop_loss_price){

                    Signal::where('id', $signal->id)
                        ->update([
                            'info' => 'stop_loss'
                        ]);
                    echo('long stop loss worked!/n');

                    // Stop execution goes here
                    app('App\Http\Controllers\API\ExecutionController')->stopLoss($signal->id);
                }
            }

            if($signal->direction == "short" && $signal->info != "stop_loss"){
                if($message[0]['lastPrice'] > (double)$signal->stop_loss_price){

                    Signal::where('id', $signal->id)
                        ->update([
                            'info' => 'stop_loss'
                        ]);
                    echo('short stop loss worked!/n');

                    // Stop execution goes here
                    app('App\Http\Controllers\API\ExecutionController')->stopLoss($signal->id);
                }
            }

            //dump($signal);
        }


        // if long: quote < stop loss price
        // if short: quote > stop loss price
        // Set status = stop_loss


        // Output column in signals.vue
        // if true -> show red filled circle
        // Add this red circle to execution.vue (to the header)
    }

}