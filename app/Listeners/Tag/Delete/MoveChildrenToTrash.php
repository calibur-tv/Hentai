<?php

namespace App\Listeners\Tag\Delete;

use App\Models\Tag;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MoveChildrenToTrash
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

        Tag
            ::where('parent_slug', $tag->slug)
            ->update([
                'parent_slug' => config('app.tag.trash')
            ]);
    }
}
