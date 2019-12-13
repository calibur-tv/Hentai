<?php


namespace App\Listeners\Bangumi\Pass;


use App\Http\Modules\Counter\BangumiPatchCounter;

class UpdateTagCounter
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Bangumi\Pass $event)
    {
        $bangumiPatchCounter = new BangumiPatchCounter();
        $bangumiPatchCounter->add($event->bangumi->slug, 'like_user_count');
    }
}
