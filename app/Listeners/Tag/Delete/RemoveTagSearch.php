<?php


namespace App\Listeners\Tag\Delete;


use App\Models\Search;

class RemoveTagSearch
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Tag\Delete $event)
    {
        Search
            ::where('type', 1)
            ->where('slug', $event->tag->slug)
            ->delete();
    }
}
