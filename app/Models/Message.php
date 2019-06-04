<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'from_user_slug', // 触发消息的用户slug
        'to_user_slug',   // 接受消息的用户slug
    ];

    public function content()
    {
        return $this->morphOne('App\Models\Content', 'contentable');
    }
}
