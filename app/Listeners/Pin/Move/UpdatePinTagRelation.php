<?php

namespace App\Listeners\Pin\Move;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdatePinTagRelation
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
        if (!empty($event->detachTags))
        {
            $detachIds = array_map(function ($slug)
            {
                return slug2id($slug);
            }, $event->detachTags);
            $event->pin->tags()->detach($detachIds);
        }

        if (!empty($event->attachTags))
        {
            $attachIds = array_map(function ($slug)
            {
                return slug2id($slug);
            }, $event->attachTags);
            $event->pin->tags()->attach($attachIds);
        }
    }
}
