<?php

namespace App\Listeners\Pin\Reward;

use App\Http\Repositories\FlowRepository;
use App\Models\Pin;
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
     * @param  \App\Events\Pin\Reward  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Reward $event)
    {
        $pin = $event->pin;
        if ($pin->visit_type !== 0 || $pin->content_type !== 1)
        {
            return;
        }

        $tags = $pin->tags()->pluck('slug')->toArray();
        $flowRepository = new FlowRepository();

        foreach ($tags as $tagSlug)
        {
            $flowRepository->add_pin($tagSlug, $slug);
        }

        $pin->update([
            'updated_at' => Carbon::now()
        ]);
    }
}