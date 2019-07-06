<?php

namespace App\Listeners\Comment\Delete;

use App\Http\Repositories\CommentRepository;
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
     * @param  \App\Events\Comment\Delete  $event
     * @return void
     */
    public function handle(\App\Events\Comment\Delete $event)
    {
        $commentRepository = new CommentRepository();
        $commentId = $event->comment->id;
        $commentRepository->item($commentId, true);
    }
}
