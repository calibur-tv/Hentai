<?php

namespace App\Listeners\Pin\Create;

use App\Http\Repositories\PinRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefreshUserDrafts
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
     * @param  ExampleEvent  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Create $event)
    {
        $pin = $event->pin;
        if ($pin->visit_type == 0)
        {
            return;
        }

        $pinRepository = new PinRepository();
        $pinRepository->drafts($pin->user_slug, 0, 0, true);
    }
}
