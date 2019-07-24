<?php

namespace App\Listeners\Tag\Create;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateQuestionRule
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
     * @param  \App\Events\Tag\Create  $event
     * @return void
     */
    public function handle(\App\Events\Tag\Create $event)
    {
        $tag = $event->tag;
        if (
            !in_array($tag->parent_slug, [
                config('app.tag.topic'),
                config('app.tag.bangumi'),
                config('app.tag.game')
            ])
        )
        {
            return;
        }

        $tag->rule()->create([
            'question_count' => 10,
            'qa_minutes' => 5,
            'right_rate' => 100,
            'result_type' => $tag->parent_slug === config('app.tag.topic') ? 1 : 0
        ]);
    }
}
