<?php

namespace App\Listeners\Pin\Update;

use App\Http\Modules\Counter\TagPatchCounter;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateTagCounter
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
        if ($event->doPublish)
        {
            $tagPatchCounter = new TagPatchCounter();

            foreach ($event->detachTags as $tagSlug)
            {
                $tagPatchCounter->add($tagSlug, 'pin_count', -1);
            }

            foreach ($event->attachTags as $tagSlug)
            {
                $tagPatchCounter->add($tagSlug, 'pin_count', 1);
            }
        }
    }
}
