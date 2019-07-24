<?php


namespace App\Listeners\User\JoinZone;


use App\Http\Repositories\UserRepository;

class RefreshUserTimeline
{
    public function __construct()
    {

    }

    public function handle(\App\Events\User\JoinZone $event)
    {
        $userRepository = new UserRepository();
        $userRepository->timeline($event->user->slug, true);
    }
}
