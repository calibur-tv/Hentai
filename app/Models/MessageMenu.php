<?php

namespace App\Models;

use App\Http\Modules\RichContentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class MessageMenu extends Model
{
    protected $fillable = [
        'from_user_slug', // 触发消息的用户slug
        'to_user_slug',   // 接受消息的用户slug
        'count',          // 未读消息的条数
        'type',           // 消息的类型
    ];

    public function updateMsgMenu()
    {
        $this->increment('count');
        $cacheKey = $this::cacheKey($this->to_user_slug);
        if (Redis::EXISTS($cacheKey))
        {
            Redis::ZADD(
                $cacheKey,
                strtotime($this->updated_at) . '.' . $this->count,
                $this->type . '#' . $this->from_user_slug
            );
        }
    }

    public static function cacheKey($slug)
    {
        return "user-msg-menu:{$slug}";
    }
}
