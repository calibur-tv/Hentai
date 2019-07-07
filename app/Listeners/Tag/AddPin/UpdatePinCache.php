<?php

namespace App\Listeners\Tag\AddPin;

use App\Http\Modules\Counter\PinPatchCounter;
use App\Http\Repositories\PinRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdatePinCache
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
     * @param  \App\Events\Tag\AddPin  $event
     * @return void
     */
    public function handle(\App\Events\Tag\AddPin $event)
    {
        $tag = $event->tag;
        if (in_array($tag->slug, [
            config('app.tag.bangumi'),
            config('app.tag.game'),
            config('app.tag.topic')
        ]))
        {
            $pinRepository = new PinRepository();
            $pinRepository->item($event->pin->slug, true);
        }
        else
        {
            $pinPatchCounter = new PinPatchCounter();
            $pinPatchCounter->add($event->pin->slug, 'mark_count', 1);
        }
    }
}
