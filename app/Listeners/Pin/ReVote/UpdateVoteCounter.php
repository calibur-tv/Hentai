<?php


namespace App\Listeners\Pin\ReVote;


use App\Http\Modules\Counter\PinVoteCounter;

class UpdateVoteCounter
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Pin\ReVote $event)
    {
        $pinVoteCounter = new PinVoteCounter();
        $pinSlug = $event->pin->slug;

        $pinVoteCounter->batch($pinSlug, $event->oldAnswer, -1);
        $pinVoteCounter->batch($pinSlug, $event->answers, 1);
    }
}
