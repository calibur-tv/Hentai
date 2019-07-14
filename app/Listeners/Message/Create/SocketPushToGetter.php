<?php


namespace App\Listeners\Message\Create;


use App\Http\Modules\WebSocketPusher;
use App\Http\Transformers\Message\MessageItemResource;

class SocketPushToGetter
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Message\Create $event)
    {
        $message = $event->message;
        $getterSlug = $message->getter_slug;

        $webSocketPusher = new WebSocketPusher();
        $webSocketPusher->pushUnreadMessage($getterSlug);
        $webSocketPusher->pushUserMessageList($getterSlug);
        $webSocketPusher->pushChatMessage($getterSlug, new MessageItemResource($message));
    }
}
