<?php

namespace App\Listeners\Tag\Delete;

use App\Http\Repositories\UserRepository;
use App\User;
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
     * @param  \App\Events\Tag\Delete  $event
     * @return void
     */
    public function handle(\App\Events\Tag\Delete $event)
    {
        $tag = $event->tag;
        $user = User
            ::where('slug', $tag->creator_slug)
            ->first();

        if (is_null($user))
        {
            return;
        }

        $user
            ->timeline()
            ->where([
                'event_type' => 2,
                'event_slug' => $tag->slug
            ])
            ->delete();

        $userRepository = new UserRepository();
        $userRepository->timeline($user->slug, true);
    }
}
