<?php

namespace App\Listeners\Comment\Delete;

use App\Http\Modules\Counter\PinPatchCounter;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdatePinCounter
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
     * @param  \App\Events\Comment\Delete  $event
     * @return void
     */
    public function handle(\App\Events\Comment\Delete $event)
    {
        $pinPatchCounter = new PinPatchCounter();
        $pinPatchCounter->add($event->comment->pin_slug, 'comment_count', -1);
    }
}
