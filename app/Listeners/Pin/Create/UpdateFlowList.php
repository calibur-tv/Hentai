<?php

namespace App\Listeners\Pin\Create;

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
    public function handle(\App\Events\Pin\Create $event)
    {
        if (!$event->doPublish || $event->pin->content_type !== 1)
        {
            return;
        }

        $flowRepository = new FlowRepository();
        $slug = $event->pin->slug;

        foreach ($event->tags as $tagSlug)
        {
            $flowRepository->add_pin($tagSlug, $slug);
        }
    }
}
