<?php


namespace App\Listeners\Bangumi\Pass;


use App\Http\Repositories\UserRepository;
use App\Models\Bangumi;

class AppendUserBangumiList
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Bangumi\Pass $event)
    {
        $event->user->like($event->bangumi, Bangumi::class);
        $userRepository = new UserRepository();
        $userRepository->SortAdd(
            $userRepository->userLikeBanguiCacheKey($event->user->slug),
            $event->bangumi->slug
        );
    }
}
