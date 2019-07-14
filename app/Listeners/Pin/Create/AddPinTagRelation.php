<?php

namespace App\Listeners\Pin\Create;

use App\Http\Repositories\TagRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddPinTagRelation
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
        $arr = array_map(function ($slug)
        {
            return slug2id($slug);
        }, $event->tags);

        $event->pin->tags()->attach($arr);
    }
}
