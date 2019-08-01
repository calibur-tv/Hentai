<?php

namespace App\Listeners\Pin\Update;

use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

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
     * @param  \App\Events\Pin\Update  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Update $event)
    {
        Log::info('update pin tag relation begin');

        $pin = $event->pin;

        if (!empty($event->detachTags))
        {
            $detachIds = array_map(function ($slug)
            {
                return slug2id($slug);
            }, $event->detachTags);
            $pin->tags()->detach($detachIds);
        }

        if (!empty($event->attachTags))
        {
            $attachIds = array_map(function ($slug)
            {
                return slug2id($slug);
            }, $event->attachTags);
            $pin->tags()->attach($attachIds);
        }

        Log::info('update pin tag relation end');
    }
}
