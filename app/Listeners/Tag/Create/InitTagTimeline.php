<?php

namespace App\Listeners\Tag\Create;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class InitTagTimeline
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
     * @param  \App\Events\Tag\Create  $event
     * @return void
     */
    public function handle(\App\Events\Tag\Create $event)
    {
        if ($event->isIdol)
        {
            return;
        }
        $tag = $event->tag;
        $tag->timeline()->create([
            'event_type' => 1,
            'event_slug' => $event->user->slug
        ]);
    }
}
