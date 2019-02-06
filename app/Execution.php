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

        'out_percent_1',
        'out_price_1',
        'out_volume_1',
        'out_response_1',
        'out_value_1', // Trade execution value
        'out_status_1',
        'out_balance_status_1',
        'out_balance_response_1',
        'out_balance_value_1', // Balance value

        'out_percent_2',
        'out_price_2',
        'out_volume_2',
        'out_response_2',
        'out_value_2',
        'out_status_2',
        'out_balance_status_2',
        'out_balance_response_2',
        'out_balance_value_2',

        'out_percent_3',
        'out_price_3',
        'out_volume_3',
        'out_response_3',
        'out_value_3',
        'out_status_3',
        'out_balance_status_3',
        'out_balance_response_3',
        'out_balance_value_3',

        'out_percent_4',
        'out_price_4',
        'out_volume_4',
        'out_response_4',
        'out_value_4',
        'out_status_4',
        'out_balance_status_1',
        'out_balance_response_1',
        'out_balance_value_1',

    ];

}
