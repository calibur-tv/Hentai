<?php

namespace App\Listeners\Pin\Delete;

use App\Http\Repositories\UserRepository;
use App\User;
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
     * @param  \App\Events\Pin\Delete  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Delete $event)
    {
        if (!$event->published)
        {
            return;
        }

        $pin = $event->pin;
        $user = User
            ::where('slug', $pin->user_slug)
            ->first();

        if (is_null($user))
        {
            return;
        }

        $user
            ->timeline()
            ->where([
                'event_type' => 3,
                'event_slug' => $pin->slug
            ])
            ->delete();

        $userRepository = new UserRepository();
        $userRepository->timeline($user->slug, true);
    }
}
