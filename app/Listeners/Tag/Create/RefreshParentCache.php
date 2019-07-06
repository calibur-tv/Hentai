<?php

namespace App\Listeners\Tag\Create;

use App\Http\Repositories\TagRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefreshParentCache
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
     * @param  ExampleEvent  $event
     * @return void
     */
    public function handle(\App\Events\Tag\Create $event)
    {
        $tagRepository = new TagRepository();
        $tagRepository->relation_item($event->tag->parent_slug, true);
    }
}