<?php

namespace App\Listeners\User\ToggleFollowUser;

use App\Http\Modules\Counter\UserPatchCounter;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateRelationCounter
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
     * @param  \App\Events\User\ToggleFollowUser  $event
     * @return void
     */
    public function handle(\App\Events\User\ToggleFollowUser $event)
    {
        $userPatchCounter = new UserPatchCounter();
        $num = $event->result ? 1 : -1;
        $userPatchCounter->add($event->user->slug, 'following_count', $num);
        $userPatchCounter->add($event->target->slug, 'followers_count', $num);
        $userPatchCounter->add($event->target->slug, 'friends_count', $num);
    }
}
