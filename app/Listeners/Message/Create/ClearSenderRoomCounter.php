<?php


namespace App\Listeners\Message\Create;


use App\Models\MessageMenu;
use Illuminate\Support\Facades\Redis;

class ClearSenderRoomCounter
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Message\Create $event)
    {
        $message = $event->message;

        /**
         * 在查询 menu 的时候，getter 为当前查询用户
         * 因此这个时候，sender 的 menuItem 的 getter_slug 其实是 message->sender_slug
         */
        $senderMenuItem = MessageMenu::firstOrCreate([
            'getter_slug' => $message->sender_slug,
            'sender_slug' => $message->getter_slug,
            'type' => $message->type
        ]);

        $senderMenuItem->update([
            'count' => 0
        ]);

        /**
         * 这个地方的 menuList 仍然是读取 sender 的 slug（主要还是要把当前 sender 看做读数据时的 getter）
         */
        $menuListCacheKey = MessageMenu::messageListCacheKey($message->sender_slug);
        if (Redis::EXISTS($menuListCacheKey))
        {
            Redis::ZADD(
                $menuListCacheKey,
                $senderMenuItem->generateCacheScore(),
                $event->roomId
            );
        }
    }
}
