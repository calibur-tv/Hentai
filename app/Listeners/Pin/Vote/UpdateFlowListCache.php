<?php

namespace App\Listeners\Pin\Vote;

use App\Http\Repositories\FlowRepository;
use Carbon\Carbon;
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
     * @param  \App\Events\Pin\Vote  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Vote $event)
    {
        $pin = $event->pin;
        if (!$pin->published_at || $pin->content_type !== 1)
        {
            return;
        }

        $tags = $pin->tags()->pluck('slug')->toArray();
        $flowRepository = new FlowRepository();

        foreach ($tags as $tagSlug)
        {
            $flowRepository->update_pin($tagSlug, $pin->slug);
        }

        $pin->update([
            'updated_at' => Carbon::now()
        ]);
    }
}
