<?php

namespace App\Listeners\Tag\RemovePin;

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
     * @param  \App\Events\Tag\RemovePin  $event
     * @return void
     */
    public function handle(\App\Events\Tag\RemovePin $event)
    {
        $tag = $event->tag;
        if ($tag->parent_slug == config('app.tag.notebook'))
        {
            $pinPatchCounter = new PinPatchCounter();
            $pinPatchCounter->add($event->pin->slug, 'mark_count', -1);
        }
        else
        {
            $pinRepository = new PinRepository();
            $pinRepository->item($event->pin->slug, true);
        }
    }
}
