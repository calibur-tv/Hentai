<?php


namespace App\Listeners\Pin\Delete;


use App\Models\Search;

class RemovePinSearch
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Pin\Delete $event)
    {
        if ($event->pin->content_type != 1)
        {
            return;
        }

        Search
            ::where('type', 2)
            ->where('slug', $event->pin->slug)
            ->delete();
    }
}
