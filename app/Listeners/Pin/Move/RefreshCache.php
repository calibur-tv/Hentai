<?php

namespace App\Listeners\Pin\Move;

use App\Http\Repositories\PinRepository;

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
     * @param  \App\Events\Pin\Move  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Move $event)
    {
        $pinRepository = new PinRepository();
        $pinRepository->item($event->pin->slug, true);
    }
}
