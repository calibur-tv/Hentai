<?php

namespace App\Listeners\Tag\Update;

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
     * @param  \App\Events\Tag\Update  $event
     * @return void
     */
    public function handle(\App\Events\Tag\Update $event)
    {
        if ($event->isIdol)
        {
            return;
        }
        $tag = $event->tag;
        $tag->timeline()->create([
            'event_type' => 2,
            'event_slug' => $event->user->slug
        ]);
    }
}
