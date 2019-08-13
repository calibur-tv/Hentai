<?php

namespace App\Listeners\Pin\Move;

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
     * @param  \App\Events\Pin\Move  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Move $event)
    {
        $event->pin->timeline()->create([
            'event_type' => 5,
            'event_slug' => $event->user->slug
        ]);
    }
}
