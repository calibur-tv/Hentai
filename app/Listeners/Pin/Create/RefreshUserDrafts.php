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
     * @param  \App\Events\Pin\Create  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Create $event)
    {
        if ($event->doPublish || $event->pin->content_type !== 1)
        {
            return;
        }

        $pinRepository = new PinRepository();
        $pinRepository->drafts($event->pin->user_slug, 0, 0, true);
    }
}
