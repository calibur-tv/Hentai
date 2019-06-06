<?php

namespace App\Models;

use App\Http\Modules\RichContentService;
use Illuminate\Database\Eloquent\Model;

class MessageMenu extends Model
{
    protected $fillable = [
        'from_user_slug', // 触发消息的用户slug
        'to_user_slug',   // 接受消息的用户slug
        'count',          // 未读消息的条数
        'type',           // 消息的类型
    ];
}
