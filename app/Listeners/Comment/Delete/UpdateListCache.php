<?php

namespace App\Listeners\Comment\Delete;

use App\Http\Repositories\CommentRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateListCache
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
     * @param  \App\Events\Comment\Delete  $event
     * @return void
     */
    public function handle(\App\Events\Comment\Delete $event)
    {
        $commentRepository = new CommentRepository();
        $pinSlug = $event->comment->pin_slug;
        $commentId = $event->comment->id;
        $timelineCacheKey = $commentRepository->timeline_comment_cache_key($pinSlug);
        $hottestCacheKey = $commentRepository->hottest_comment_cache_key($pinSlug);

        $commentRepository->ListRemove($timelineCacheKey, $commentId);
        $commentRepository->SortRemove($hottestCacheKey, $commentId);
    }
}
