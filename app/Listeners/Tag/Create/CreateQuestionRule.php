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
        $tag->rule()->create([
            'question_count' => 30,
            'qa_minutes' => 30,
            'right_rate' => 100
        ]);
    }
}
