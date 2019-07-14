<?php


namespace App\Listeners\Message\Create;


use App\Http\Modules\Counter\UnreadMessageCounter;

class IncrementGetterUnreadMessageCount
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Message\Create $event)
    {
        $UnreadMessageCounter = new UnreadMessageCounter();
        $UnreadMessageCounter->add($event->message->getter_slug);
    }
}
