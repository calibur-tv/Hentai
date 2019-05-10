<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSign extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'time'
    ];
}
