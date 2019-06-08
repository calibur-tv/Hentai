<?php


namespace App\Http\Modules;


use App\Http\Modules\Counter\UnreadMessageCounter;

class WebSocketPusher
{
    public function pushUnreadMessage($slug, $server = null, $fd = null)
    {
        if ($fd)
        {
            $targetFd = $fd;
        }
        else
        {
            $targetFd = app('swoole')
                ->wsTable
                ->get('uid:' . $slug);

            if (false === $targetFd)
            {
                return;
            }
            $targetFd = $targetFd['value'];
        }

        $pusher = $server ?: app('swoole');
        $UnreadMessageCounter = new UnreadMessageCounter();

        $pusher->push($targetFd, json_encode([
            'channel' => 0,
            'unread_message_total' => $UnreadMessageCounter->get($slug),
            'unread_notice_total' => 0
        ]));
    }
}