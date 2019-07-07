<?php

namespace App\Listeners\Pin\Reward;

use App\Http\Modules\Counter\CommentLikeCounter;
use App\Http\Modules\Counter\PinPatchCounter;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateLikeCounter
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
     * @param  \App\Events\Pin\Reward  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Reward $event)
    {
        $pinPatchCounter = new PinPatchCounter();
        $pinPatchCounter->add($event->pin->slug, 'reward_count', 1);
    }
}
