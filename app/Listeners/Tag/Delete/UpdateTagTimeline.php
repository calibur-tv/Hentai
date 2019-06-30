<?php

namespace App\Listeners\Tag\Delete;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateTagTimeline
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
        $tag = $event->tag;
        $tag->timeline()->create([
            'event_type' => 3,
            'event_slug' => $event->user->slug
        ]);
    }
}
