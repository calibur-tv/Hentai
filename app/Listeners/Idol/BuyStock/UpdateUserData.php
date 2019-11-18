<?php

namespace App\Listeners\Idol\BuyStock;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateUserData
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

    public function handle(\App\Events\Idol\BuyStock $event)
    {
        $event->user->increment('buy_idol_count', $event->coinAmount);
    }
}
