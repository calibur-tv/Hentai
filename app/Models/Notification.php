<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /**
     * type
     * 1：回复主题帖
     * 2：回复楼层贴
     * 3：喜欢了主题帖
     * 4：赞同了楼层贴
     * 5：赞了图片
     */
    protected $fillable = [
        'checked',      // 是否已读
        'from_user_id', // 触发消息的用户id
        'to_user_id',   // 接受消息的用户id
        'type',         // 通知的类型，枚举
        'model_id',
        'comment_id',
        'message',      // 拼接好的消息的 html
    ];
}
