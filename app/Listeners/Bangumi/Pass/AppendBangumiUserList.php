<?php


namespace App\Listeners\Bangumi\Pass;


use App\Http\Repositories\BangumiRepository;

class AppendBangumiUserList
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Bangumi\Pass $event)
    {
        $event->bangumi->increment('like_user_count');
        $bangumiRepository = new BangumiRepository();
        $bangumiRepository->SortAdd(
            $bangumiRepository->bangumiLikerCacheKey($event->bangumi->slug),
            $event->user->slug
        );
    }
}
