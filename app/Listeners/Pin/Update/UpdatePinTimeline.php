<?php

namespace App\Listeners\Pin\Update;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdatePinTimeline
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
     * @param  \App\Events\Pin\Update  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Update $event)
    {
        $pin = $event->pin;
        $pin->timeline()->create([
            'event_type' => 1,
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
