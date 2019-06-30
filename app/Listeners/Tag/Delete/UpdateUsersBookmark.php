<?php

namespace App\Listeners\Tag\Delete;

use App\Http\Repositories\TagRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateUsersBookmark
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
    public function handle(\App\Events\Tag\Delete $event)
    {
        $users = $event->tag
            ->bookmarkers()
            ->pluck('slug')
            ->toArray();

        $tagRepository = new TagRepository();
        foreach ($users as $slug)
        {
            $tagRepository->bookmarks($slug, true);
        }
    }
}
