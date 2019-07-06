<?php

namespace App\Listeners\User\ToggleFollowUser;

use App\Http\Repositories\UserRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefreshRelationCache
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
        $userRepository = new UserRepository();
        $mineSlug = $event->user->slug;
        $targetSlug = $event->target->slug;

        if ($event->result)
        {
            $userRepository->SortAdd($userRepository->followers_cache_key($targetSlug), $mineSlug);
            $userRepository->ListInsertBefore($userRepository->followings_cache_key($mineSlug), $targetSlug);
            if ($event->followMe)
            {
                $userRepository->ListInsertBefore($userRepository->friends_cache_key($targetSlug), $mineSlug);
                $userRepository->ListInsertBefore($userRepository->friends_cache_key($mineSlug), $targetSlug);
            }
        }
        else
        {
            $userRepository->SortRemove($userRepository->followers_cache_key($targetSlug), $mineSlug);
            $userRepository->ListRemove($userRepository->followings_cache_key($mineSlug), $targetSlug);
            if ($event->followMe)
            {
                $userRepository->ListRemove($userRepository->friends_cache_key($targetSlug), $mineSlug);
                $userRepository->ListRemove($userRepository->friends_cache_key($mineSlug), $targetSlug);
            }
        }
    }
}
