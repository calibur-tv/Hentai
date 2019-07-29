<?php

namespace App\Listeners\Pin\Update;

use App\Http\Repositories\PinRepository;
use App\Http\Repositories\UserRepository;
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
     * @param  \App\Events\Pin\Update  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Update $event)
    {
        if (!$event->doPublish)
        {
            return;
        }

        $event->user->timeline()->create([
            'event_type' => 3,
            'event_slug' => $event->pin->slug
        ]);

        $userRepository = new UserRepository();
        $userRepository->timeline($event->user->slug, true);
    }
}
