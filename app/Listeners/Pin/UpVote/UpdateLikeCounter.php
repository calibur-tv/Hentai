<?php

namespace App\Listeners\Pin\UpVote;

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
     * @param  \App\Events\Pin\UpVote  $event
     * @return void
     */
    public function handle(\App\Events\Pin\UpVote $event)
    {
        $pinPatchCounter = new PinPatchCounter();
        $pinPatchCounter->add($event->pin->slug, 'like_count', $event->result ? 1 : -1);
    }
}
