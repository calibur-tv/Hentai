<?php

namespace App\Listeners\Comment\UpVote;

use App\Http\Modules\Counter\CommentLikeCounter;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateLikeCounter
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
        $commentLikeCounter = new CommentLikeCounter();
        $commentLikeCounter->add($event->comment->id, $event->result ? 1 : -1);
    }
}
