<?php

namespace App\Listeners\Tag\Update;

use App\Http\Repositories\TagRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefreshCache
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
    public function handle(\App\Events\Tag\Update $event)
    {
        $tag = $event->tag;
        $tagRepository = new TagRepository();

        $tagRepository->item($tag->slug, true);
        $tagRepository->relation_item($tag->slug, true);
        $tagRepository->relation_item($tag->parent_slug, true);

        if ($tag->parent_slug === config('app.tag.notebook'))
        {
            $tagRepository->bookmarks($tag->creator_slug, true);
        }
    }
}
