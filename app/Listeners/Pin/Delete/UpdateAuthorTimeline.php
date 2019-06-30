<?php

namespace App\Listeners\Pin\Delete;

use App\Http\Repositories\PinRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateAuthorTimeline
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
        $event->user
            ->timeline()
            ->where([
                'event_type' => 3,
                'event_slug' => $event->pin->slug
            ])
            ->delete();
    }
}
