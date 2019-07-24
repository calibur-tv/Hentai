<?php


namespace App\Listeners\User\JoinZone;


use App\Http\Repositories\TagRepository;

class RefreshUserBookmark
{
    public function __construct()
    {

    }

    public function handle(\App\Events\User\JoinZone $event)
    {
        $tagRepository = new TagRepository();
        $tagRepository->bookmarks($event->user->slug, true);
    }
}
