<?php

namespace App\Listeners\Pin\Delete;

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
    public function handle(\App\Events\Pin\Delete $event)
    {
        if ($event->published)
        {
            $flowRepository = new FlowRepository();
            $tagRepository = new TagRepository();

            $tags = $event->pin
                ->tags()
                ->pluck('slug')
                ->toArray();;
            $slug = $event->pin->slug;

            foreach ($tags as $item)
            {
                $tag = $tagRepository->item($item);
                $flowRepository->del_pin($tag, $slug);
            }
        }
    }
}
