<?php

namespace App\Listeners\Comment\Create;

use App\Http\Repositories\CommentRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCommentListCache
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
     * @param  \App\Events\Comment\Create  $event
     * @return void
     */
    public function handle(\App\Events\Comment\Create $event)
    {
        $commentRepository = new CommentRepository();
        $pinSlug = $event->comment->pin_slug;
        $commentId = $event->comment->id;
        $timelineCacheKey = $commentRepository->timeline_comment_cache_key($pinSlug);
        $hottestCacheKey = $commentRepository->hottest_comment_cache_key($pinSlug);

        $commentRepository->ListInsertAfter($timelineCacheKey, $commentId);
        $commentRepository->SortAdd($hottestCacheKey, $commentId);
    }
}
