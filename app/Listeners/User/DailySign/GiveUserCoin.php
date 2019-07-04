<?php

namespace App\Listeners\User\DailySign;

use App\Http\Modules\DailyRecord\UserActivity;
use App\Http\Modules\VirtualCoinService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class GiveUserCoin
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
        $virtualCoinService = new VirtualCoinService();
        $virtualCoinService->daySign($event->user->slug, $event->coin);
    }
}
