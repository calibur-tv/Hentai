<?php


namespace App\Listeners\Pin\Vote;


use App\Http\Modules\Counter\PinVoteCounter;

class UpdateVoteCounter
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Pin\Vote $event)
    {
        $pinVoteCounter = new PinVoteCounter();
        $pinSlug = $event->pin->slug;

        foreach ($event->answers as $ans)
        {
            $pinVoteCounter->add($pinSlug, $ans, 1, false);
        }
    }
}
