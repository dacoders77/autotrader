<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 12/4/2018
 * Time: 2:55 PM
 */

namespace App\Classes;

use App\Events\AttrUpdateEvent;
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

        //event(new AttrUpdateEvent($message[0]['lastPrice']));
        event(new AttrUpdateEvent(['signal' => Signal::paginate(), 'symbol' => Symbol::paginate()])); // Event is received in signals.vue, symbols.vue
    }

    public  static function stopLossCheck($message){
        // Get all signals with -ticker
        /* We don't have execution name in signals table, but we do in symbols */
        $executionSymbolName = Symbol::where('leverage_name', $message[0]['symbol'])->value('execution_name');
        // Run through this array

        dump($message[0]['symbol'] . " " . $message[0]['lastPrice']);

        foreach (Signal::where('symbol', $executionSymbolName)->get() as $signal){
            //dump('price /stop_loss_value: ' . $message[0]['lastPrice'] . " " . $signal->stop_loss_price . " " . gettype($message[0]['lastPrice']) . " " . gettype($signal->stop_loss_price));

            if($signal->direction == "long" && $signal->info != "stop_loss"){
                if($message[0]['lastPrice'] < (double)$signal->stop_loss_price){

                    Signal::where('id', $signal->id)
                        ->update([
                            'info' => 'stop_loss'
                        ]);
                    echo('long stop loss worked!http/n');
                }
            }

            if($signal->direction == "short" && $signal->info != "stop_loss"){
                if($message[0]['lastPrice'] > (double)$signal->stop_loss_price){

                    Signal::where('id', $signal->id)
                        ->update([
                            'info' => 'stop_loss'
                        ]);
                    echo('short stop loss worked!/n');
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