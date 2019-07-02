<?php

namespace App\Listeners\Tag\Create;

use App\Http\Repositories\UserRepository;
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
    public function handle(\App\Events\Tag\Create $event)
    {
        $user = $event->user;
        $user->timeline()->create([
            'event_type' => 2,
            'event_slug' => $event->tag->slug
        ]);

        $userRepository = new UserRepository();
        $userRepository->timeline($user->slug, true);
    }
}
