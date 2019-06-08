<?php


namespace App\Http\Modules;


use App\Http\Modules\Counter\UnReadMessageCounter;

class WebSocketPusher
{
    public function pushUnReadMessage($slug, $server = null, $fd = null)
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
        $unReadMessageCounter = new UnReadMessageCounter();

        $pusher->push($targetFd, json_encode([
            'unread_message_total' => $unReadMessageCounter->get($slug),
            'unread_notice_total' => 0
        ]));
    }
}
