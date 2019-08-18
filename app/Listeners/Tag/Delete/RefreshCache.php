<?php

namespace App\Listeners\Tag\Delete;

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
     * @param  \App\Events\Tag\Delete  $event
     * @return void
     */
    public function handle(\App\Events\Tag\Delete $event)
    {
        $tag = $event->tag;
        $tagRepository = new TagRepository();

        $tagRepository->item($tag->slug, true);
        $tagRepository->children($tag->parent_slug, true);

        if ($tag->parent_slug === config('app.tag.notebook'))
        {
            $tagRepository->bookmarks($tag->creator_slug, true);
        }
    }
}
