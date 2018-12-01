<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Client extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'last_name',
        'telegram',
        'valid',
        'active',
        'balance_symbols',
        'email',
        'api',
        'api_secret',
        'info'
    ];
}
