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
        Search
            ::where('type', 2)
            ->where('slug', $event->pin->slug)
            ->delete();
    }
}
