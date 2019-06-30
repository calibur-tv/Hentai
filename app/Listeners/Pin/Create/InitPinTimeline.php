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
     * @param  ExampleEvent  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Create $event)
    {
        $pin = $event->pin;

        $pin->timeline()->create([
            'event_type' => 0,
            'event_slug' => $pin->user_slug
        ]);
    }
}
