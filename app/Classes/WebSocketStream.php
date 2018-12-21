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
            ->where('info', null) // Update quotes only for signal which was not close with stop loss
            //->where('status', '!=', 'new') // Or manually closed via stop button click
            ->update([
                'quote_value' => $message[0]['lastPrice']
            ]);

        $quoteTickTime = strtotime($message[0]['timestamp']);
        /* Send event to vue.js once a second. Otherwise pusher gets out of free messages limit. */
        if (self::$isFirstTimeTickCheck || $quoteTickTime >= self::$addedTickTime){
            self::$isFirstTimeTickCheck = false;
            self::$addedTickTime = $quoteTickTime + 1; // Allow ticks not frequenter than twice a second
            try{
                /* Event is received in signals.vue, symbols.vue */
                event(new AttrUpdateEvent([
                    'signal' => Signal::paginate(),
                    'symbol' => Symbol::paginate(),
                    'ticker' => $message[0]['symbol'],
                    'price' => $message[0]['lastPrice']
                ]));
            }
            catch (\Exception $e){
                Log::info('Pusher error: ' . __FILE__ . ' ' . __LINE__ . ' ' . Signal::paginate() . ' ' . Symbol::paginate());
                throw new Exception($e);
            }
        }
    }

    /**
     * On each quote tick we check it for the stop loss.
     * When a signal is closed with the stop loss or manually - we don't fire stop loss anymore.
     *
     * @param array $message
     * @return void
     */
    public  static function stopLossCheck($message){
        /* We don't have execution name in signals table, but we do in symbols */
        $executionSymbolName = Symbol::where('leverage_name', $message[0]['symbol'])->value('execution_name');
        /* Run through all signals array */
        foreach (Signal::where('symbol', $executionSymbolName)->get() as $signal){
            /* Stop loss for long positions */
            if($signal->direction == "long" ){
                /** Fire stop loss only for not closed positions (info == null, when not null == manual_close or stop_loss)
                 * And for only open positions: status == new or error */
                if($signal->info == null && ($signal->status == "success" || $signal->status == "error")){
                    //LogToFile::add(__FILE__ . __LINE__, " stop_loss for longs " . $signal->id . 'signal_status: ' . $signal->info    );
                    if($message[0]['lastPrice'] < (double)$signal->stop_loss_price){
                        Signal::where('id', $signal->id)
                            ->update([
                                'info' => 'stop_loss'
                            ]);
                        /* Initiate stop button click via controller */
                        app('App\Http\Controllers\API\ExecutionController')->stopLoss($signal->id);
                    }
                }
            }

            /* Stop loss for short positions */
            if($signal->direction == "short" ){
                if($signal->info == null && ($signal->status == "success" || $signal->status == "error")){
                    if($message[0]['lastPrice'] > (double)$signal->stop_loss_price){
                        Signal::where('id', $signal->id)
                            ->update([
                                'info' => 'stop_loss'
                            ]);
                        app('App\Http\Controllers\API\ExecutionController')->stopLoss($signal->id);
                    }
                }
            }
        }
    }
}