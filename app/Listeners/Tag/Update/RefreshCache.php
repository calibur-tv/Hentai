<?php

namespace App\Listeners\Tag\Update;

use App\Http\Repositories\TagRepository;
use App\Http\Repositories\UserRepository;
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
     * @param  \App\Events\Tag\Update  $event
     * @return void
     */
    public function handle(\App\Events\Tag\Update $event)
    {
        $tag = $event->tag;
        $tagRepository = new TagRepository();

        $tagRepository->item($tag->slug, true);
        $tagRepository->children($tag->parent_slug, 0, 0, true);

        if ($tag->parent_slug === config('app.tag.notebook'))
        {
            $tagRepository->bookmarks($tag->creator_slug, true);

            $userRepository = new UserRepository();
            $userRepository->timeline($tag->creator_slug, true);
        }
    }
}
