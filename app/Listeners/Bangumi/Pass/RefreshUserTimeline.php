<?php


namespace App\Listeners\Bangumi\Pass;


use App\Http\Repositories\UserRepository;

class RefreshUserTimeline
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Bangumi\Pass $event)
    {
        $event->user->timeline()->create([
            'event_type' => 4,
            'event_slug' => $event->bangumi->slug
        ]);
        $userRepository = new UserRepository();
        $userRepository->timeline($event->user->slug, true);
    }
}
