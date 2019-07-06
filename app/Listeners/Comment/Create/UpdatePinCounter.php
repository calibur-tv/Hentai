<?php

namespace App\Listeners\Comment\Create;

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
     * @param  \App\Events\Comment\Create  $event
     * @return void
     */
    public function handle(\App\Events\Comment\Create $event)
    {
        $pinPatchCounter = new PinPatchCounter();
        $pinPatchCounter->add($event->comment->pin_slug, 'comment_count');
    }
}
