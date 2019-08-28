<?php

namespace App\Listeners\Pin\Update;

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
     * @param  \App\Events\Pin\Update  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Update $event)
    {
        $pin = $event->pin;
        $pinRepository = new PinRepository();
        $pinRepository->item($pin->slug, true);

        if ($event->doPublish)
        {
            $pinRepository->drafts($pin->user_slug, 0, 0, true);
        }
    }
}
