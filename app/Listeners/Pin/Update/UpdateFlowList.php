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
        if ($event->doPublish)
        {
            $flowRepository = new FlowRepository();
            $tagRepository = new TagRepository();

            $tags = $event->tags;
            $slug = $event->pin->slug;

            $notebook = $tagRepository->item($tags['notebook']);
            $area = $tagRepository->item($tags['area']);
            $topic = $tagRepository->item($tags['topic']);

            $flowRepository->add_pin($notebook, $slug);
            $flowRepository->add_pin($area, $slug);
            $flowRepository->add_pin($topic, $slug);
        }
    }
}
