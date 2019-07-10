<?php

namespace App\Listeners\Pin\Create;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class InitPinTimeline
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Pin\Create  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Create $event)
    {
        $pin = $event->pin;

        $pin->timeline()->create([
            'event_type' => 0,
            'event_slug' => $pin->user_slug
        ]);

        if ($event->doPublish)
        {
            $pin->timeline()->create([
                'event_type' => 3,
                'event_slug' => $pin->user_slug
            ]);
        }
    }
}
