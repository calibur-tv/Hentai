<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class MessageMenu extends Model
{
    protected $fillable = [
        'sender_slug',      // 触发消息的用户slug
        'getter_slug',      // 接受消息的用户slug
        'count',            // 未读消息的条数
        'type',             // 消息的类型
    ];

    public function updateGetterMenu()
    {
        $this->increment('count');
        $cacheKey = $this->cacheKey($this->getter_slug);
        if (Redis::EXISTS($cacheKey))
        {
            Redis::ZADD(
                $cacheKey,
                $this->generateCacheScore(),
                $this->type . '#' . $this->sender_slug
            );
        }
    }

    public function updateSenderMenu()
    {
        $this->update([
            'count' => 0
        ]);
        $cacheKey = $this->cacheKey($this->getter_slug);
        if (Redis::EXISTS($cacheKey))
        {
            Redis::ZADD(
                $cacheKey,
                $this->generateCacheScore(),
                $this->type . '#' . $this->sender_slug
            );
        }
    }

    public function generateCacheScore()
    {
        if (intval($this->count) > 999)
        {
            $msgCount = '999';
        }
        else
        {
            $msgCount = str_pad($this->count, 3, '0', STR_PAD_LEFT);
        }
        return strtotime($this->updated_at) . $msgCount;
    }

    public static function cacheKey($slug)
    {
        return "user-msg-menu:{$slug}";
    }
}
