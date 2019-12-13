<?php


namespace App\Listeners\Bangumi\Pass;


use App\Http\Repositories\UserRepository;

class RefreshUserCache
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Bangumi\Pass $event)
    {
        $userRepository = new UserRepository();
        $userRepository->item($event->user->slug, true);
    }
}
