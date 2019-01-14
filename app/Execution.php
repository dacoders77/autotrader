<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Execution extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        'signal_id',
        'client_id',
        'client_name',
        'symbol',

        'direction',
        'volume',
        'percent',
        'leverage',

        'client_funds_status',
        'client_funds_response',
        'client_funds_value',
        'client_funds_use', // Calculated funds to use accordingly to the given %

        'leverage_status',
        'leverage_response',
        'leverage_value',

        'small_order_status',
        'small_order_response',
        'small_order_value',

        'in_place_order_status',
        'in_place_order_response',
        'in_place_order_value',

        'in_balance',
        'in_balance_response',
        'in_balance_value',

        'out_place_order_status',
        'out_place_order_response',
        'out_place_order_value',

        'out_balance',
        'out_balance_response',
        'out_balance_value',

        'client_volume',
        'multiplier',
        'open_status',
        'close_status',

    ];

}
