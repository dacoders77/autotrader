<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Symbol extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'execution_name',
        'leverage_name',
        'formula',
        'min_exec_quantity',
        'quote_value',
        'info',
    ];

}
