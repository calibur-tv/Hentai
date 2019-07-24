<?php


namespace App\Listeners\User\JoinZone;


use App\Http\Repositories\UserRepository;

class RefreshUserCache
{
    public function __construct()
    {

    }

    public function handle(\App\Events\User\JoinZone $event)
    {
        $userRepository = new UserRepository();
        $userRepository->item($event->user->slug, true);
    }
}
