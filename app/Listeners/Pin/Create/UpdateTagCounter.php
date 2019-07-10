<?php

namespace App\Listeners\Pin\Create;

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
     * @param  \App\Events\Pin\Create  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Create $event)
    {
        if ($event->doPublish)
        {
            $list = $event->pin
                ->tags()
                ->pluck('slug')
                ->toArray();

            $tagPatchCounter = new TagPatchCounter();
            foreach ($list as $slug)
            {
                $tagPatchCounter->add($slug, 'pin_count');
            }
        }
    }
}
