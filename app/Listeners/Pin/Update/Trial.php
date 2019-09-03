<?php

namespace App\Listeners\Pin\Update;

use App\Http\Modules\RichContentService;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class Trial implements ShouldQueue
{
    use InteractsWithQueue;
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
     * @param  \App\Events\Pin\Update  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Update $event)
    {
        if (!$event->published)
        {
            return;
        }

        $pin = $event->pin;
        $content = $pin->content;

        $richContentService = new RichContentService();

        $risk = $richContentService->detectContentRisk($content);

        if ($risk['risk_score'] > 0)
        {
            $pin->deletePin(User::find(2)->first());
        }
        else if ($risk['use_review'] > 0)
        {
            $pin->reviewPin(2);
        }
    }
}
