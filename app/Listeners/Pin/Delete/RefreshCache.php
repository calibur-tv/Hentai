<?php

namespace App\Listeners\Pin\Delete;

use App\Http\Repositories\PinRepository;
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
     * @param  \App\Events\Pin\Delete  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Delete $event)
    {
        $pin = $event->pin;
        $pinRepository = new PinRepository();
        $pinRepository->item($pin->slug, true);

        if ($pin->visit_type == 1)
        {
            $pinRepository->drafts($pin->user_slug, 0, 0, true);
        }
    }
}
