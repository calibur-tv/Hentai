<?php


namespace App\Listeners\Message\Create;


use App\Http\Transformers\Message\MessageItemResource;
use Illuminate\Support\Facades\Redis;

class UpdateMessageListCache
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Message\Create $event)
    {
        $message = $event->message;
        $roomId = $event->roomId;
        if (Redis::EXISTS($roomId))
        {
            $data = new MessageItemResource($message);
            Redis::ZADD($roomId, $message->id, json_encode($data));
        }
    }
}
