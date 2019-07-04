<?php

namespace App\Listeners\User\DailySign;

use App\Http\Modules\DailyRecord\UserActivity;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateUserActivity
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
        $userActivity = new UserActivity();
        $userActivity->set($event->user->slug, $event->activity);
    }
}
