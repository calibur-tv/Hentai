<?php

namespace App\Listeners\Comment\Create;

use App\Http\Repositories\FlowRepository;
use App\Http\Repositories\PinRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateFlowListCache
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
        $pinRepository = new PinRepository();
        $comment = $event->comment;
        $slug = $comment->pin_slug;
        $pin = $pinRepository->item($slug);

        if (is_null($pin))
        {
            return;
        }

        /* 自己无法顶贴
        if ($pin->author->slug == $comment->from_user_slug && !$comment->to_user_slug)
        {
            return;
        }
        */

        $flowRepository = new FlowRepository();
        $flowRepository->add_pin($pin->notebook, $slug);
        $flowRepository->add_pin($pin->area, $slug);
        $flowRepository->add_pin($pin->topic, $slug);
    }
}
