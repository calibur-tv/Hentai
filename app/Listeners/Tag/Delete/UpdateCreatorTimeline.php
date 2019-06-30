<?php

namespace App\Listeners\Tag\Delete;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCreatorTimeline
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
    public function handle(\App\Events\Tag\Delete $event)
    {
        $event->user
            ->timeline()
            ->where([
                'event_type' => 2,
                'event_slug' => $event->tag->slug
            ])
            ->delete();
    }
}
