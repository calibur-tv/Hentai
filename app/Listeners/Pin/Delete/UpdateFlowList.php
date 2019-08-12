<?php

namespace App\Listeners\Pin\Delete;

use App\Http\Repositories\FlowRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateFlowList
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
     * @param  \App\Events\Pin\Create  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Delete $event)
    {
        if (!$event->published || $event->pin->content_type != 1)
        {
            return;
        }

        $flowRepository = new FlowRepository();

        $tags = $event->pin
            ->tags()
            ->pluck('slug')
            ->toArray();
        $pinSlug = $event->pin->slug;

        foreach ($tags as $tagSlug)
        {
            $flowRepository->del_pin($tagSlug, $pinSlug);
        }
    }
}
