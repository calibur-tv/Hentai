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
        // TODO：会出现缓存频繁读写的问题
        if ($event->followMe)
        {
            // 无论我是否取消关注，都刷新彼此朋友列表的缓存
            $userRepository->friends($event->target->slug, true);
            $userRepository->friends($event->user->slug, true);
        }
        else
        {
            // 刷新TA的粉丝列表
            $userRepository->followers($event->target->slug, true);
            // 刷新我的关注列表
            $userRepository->followings($event->user->slug, true);
        }
    }
}
