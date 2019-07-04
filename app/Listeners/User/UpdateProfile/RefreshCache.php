<?php

namespace App\Listeners\User\UpdateProfile;

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
     * @param  \App\Events\User\UpdateProfile  $event
     * @return void
     */
    public function handle(\App\Events\User\UpdateProfile $event)
    {
        $userRepository = new UserRepository();
        $userRepository->item($event->user->slug, true);
    }
}
