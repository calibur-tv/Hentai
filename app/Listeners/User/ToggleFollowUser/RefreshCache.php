<?php

namespace App\Listeners\User\ToggleFollowUser;

use App\Http\Repositories\UserRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefreshCache
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
     * @param  \App\Events\User\ToggleFollowUser  $event
     * @return void
     */
    public function handle(\App\Events\User\ToggleFollowUser $event)
    {
        $userRepository = new UserRepository();
        $userRepository->friends($event->target->slug, true);
        $userRepository->friends($event->user->slug, true);
    }
}
