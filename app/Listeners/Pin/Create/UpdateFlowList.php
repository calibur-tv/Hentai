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
        if ($event->doPublish)
        {
            $flowRepository = new FlowRepository();
            $slug = $event->pin->slug;

            $flowRepository->add_pin($event->notebook, $slug);
            $flowRepository->add_pin($event->area, $slug);
            $flowRepository->add_pin($event->topic, $slug);
        }
    }
}
