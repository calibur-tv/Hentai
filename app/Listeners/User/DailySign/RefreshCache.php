<?php

namespace App\Listeners\User\DailySign;

use App\Http\Modules\DailyRecord\UserActivity;
use App\Http\Modules\VirtualCoinService;
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
     * @param  \App\Events\User\DailySign  $event
     * @return void
     */
    public function handle(\App\Events\User\DailySign $event)
    {
        $userRepository = new UserRepository();
        $userRepository->item($event->user->slug, true);
    }
}
