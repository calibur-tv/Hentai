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
     * @param  \App\Events\Tag\Create  $event
     * @return void
     */
    public function handle(\App\Events\Tag\Create $event)
    {
        if ($event->isIdol)
        {
            return;
        }
        if (
            !in_array($event->tag->parent_slug, [
                config('app.tag.topic'),
                config('app.tag.bangumi'),
                config('app.tag.game')
            ])
        )
        {
            return;
        }

        $user = $event->user;
        $user->timeline()->create([
            'event_type' => 2,
            'event_slug' => $event->tag->slug
        ]);

        $userRepository = new UserRepository();
        $userRepository->timeline($user->slug, true);
    }
}
