<?php

namespace App\Listeners\Tag\Create;

use App\Http\Repositories\TagRepository;
use App\Models\Tag;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCreatorBookmark
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
        $user = $event->user;
        $user->bookmark($event->tag, Tag::class);

        $tagRepository = new TagRepository();
        $tagRepository->bookmarks($user->slug, true);
    }
}
