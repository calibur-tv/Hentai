<?php

namespace App\Listeners\Comment\Create;

use App\Http\Repositories\FlowRepository;
use App\Models\Pin;
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
        $comment = $event->comment;
        $slug = $comment->pin_slug;
        $pin = Pin::where('slug', $slug)->first();

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

        $tags = $pin->tags()->pluck('slug')->toArray();
        $flowRepository = new FlowRepository();

        foreach ($tags as $tagSlug)
        {
            $flowRepository->add_pin($tagSlug, $slug);
        }
    }
}
