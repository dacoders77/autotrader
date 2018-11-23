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
        'multiplier',
        'direction',
        'volume',
        'percent',
        'leverage',
        'leverage_response',
        'client_funds',
        'client_volume',
        'multiplier',
        'open_status',
        'close_status',

    ];

}
