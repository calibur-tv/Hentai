<?php

namespace App\Models;

use App\Http\Modules\RichContentService;
use Illuminate\Database\Eloquent\Model;

class MessageMenu extends Model
{
    protected $fillable = [
        'from_user_id', // 触发消息的用户id
        'to_user_id',   // 接受消息的用户id
        'count',        // 未读消息的条数
        'type',         // 消息的类型
    ];
}
