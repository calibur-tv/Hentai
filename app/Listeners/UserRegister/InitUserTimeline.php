<?php

namespace App\Listeners\UserRegister;

use App\Events\UserRegister;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class InitUserTimeline
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
    public function handle(UserRegister $event)
    {
        $user = $event->user;
        $user->timeline()->create([
            'event_type' => 0,
            'event_slug' => $user->invitor_slug
        ]);
    }
}
