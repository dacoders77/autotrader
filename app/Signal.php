<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'symbol',
        'percent',
        'leverage',
        'direction',
        'stop_loss_price',

        'out_percent_1',
        'out_price_1',
        'out_volume_1',
        'out_response_1',
        'out_value_1',
        'out_status_1',

        'out_percent_2',
        'out_price_2',
        'out_volume_2',
        'out_response_2',
        'out_value_2',
        'out_status_2',

        'out_percent_3',
        'out_price_3',
        'out_volume_3',
        'out_response_3',
        'out_value_3',
        'out_status_3',

        'out_percent_4',
        'out_price_4',
        'out_volume_4',
        'out_response_4',
        'out_value_4',
        'out_status_4'
    ];
}
