<?php

namespace App\Listeners\Pin\Create;

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
    public function handle(\App\Events\Pin\Create $event)
    {
        if ($event->pin->visit_type != 0)
        {
            $event->user->timeline()->create([
                'event_type' => 3,
                'event_slug' => $event->pin->slug
            ]);
        }
    }
}
