<?php

namespace App\Listeners\Pin\Update;

use App\Http\Repositories\PinRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

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
        Log::info('update pin refresh cache begin');

        $pin = $event->pin;
        $pinRepository = new PinRepository();
        $pinRepository->item($pin->slug, true);

        // $pinRepository->DeletePage("/pin/{$pin->slug}");

        if ($event->doPublish)
        {
            $pinRepository->drafts($pin->user_slug, 0, 0, true);
        }

        Log::info('update pin refresh cache end');
    }
}
