<?php

namespace App\Listeners\Comment\UpVote;

use App\Http\Repositories\CommentRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateHottestCache
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
     * @param  \App\Events\Comment\UpVote  $event
     * @return void
     */
    public function handle(\App\Events\Comment\UpVote $event)
    {
        $commentRepository = new CommentRepository();

        $commentRepository->SortAdd(
            $commentRepository->hottest_comment_cache_key($event->comment->pin_slug),
            $event->comment->id, $event->result ? 10000 : -10000
        );
    }
}
