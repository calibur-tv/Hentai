<?php

namespace App\Listeners\Pin\Update;

use App\Http\Repositories\FlowRepository;
use App\Http\Repositories\TagRepository;
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
    public function handle(\App\Events\Pin\Update $event)
    {
        if ($event->published && !$event->dontAddFlow)
        {
            $flowRepository = new FlowRepository();
            $pinSlug = $event->pin->slug;

            foreach ($event->detachTags as $tagSlug)
            {
                $flowRepository->del_pin($tagSlug, $pinSlug);
            }

            foreach ($event->attachTags as $tagSlug)
            {
                $flowRepository->add_pin($tagSlug, $pinSlug);
            }
        }
    }
}
