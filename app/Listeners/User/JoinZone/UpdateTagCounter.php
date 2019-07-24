<?php


namespace App\Listeners\User\JoinZone;


use App\Http\Modules\Counter\TagPatchCounter;

class UpdateTagCounter
{
    public function __construct()
    {

    }

    public function handle(\App\Events\User\JoinZone $event)
    {
        $tagPatchCounter = new TagPatchCounter();
        $tagPatchCounter->add($event->tag->slug, 'seen_user_count');
    }
}
