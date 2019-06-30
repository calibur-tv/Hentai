<?php

namespace App\Listeners\Pin\Delete;

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
     * @param  ExampleEvent  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Delete $event)
    {
        $event->pin->timeline()->create([
            'event_type' => 2,
            'event_slug' => $event->user->slug
        ]);
    }
}
