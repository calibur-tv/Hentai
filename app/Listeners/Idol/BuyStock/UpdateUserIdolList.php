<?php

namespace App\Listeners\Idol\BuyStock;

use App\Http\Repositories\IdolRepository;
use App\Http\Repositories\UserRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateUserIdolList
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

    public function handle(\App\Events\Idol\BuyStock $event)
    {
        $userSlug = $event->user->slug;
        $idolSlug = $event->idol->slug;

        $userRepository = new UserRepository();
        $userRepository->SortAdd($userRepository->bangumiIdolsCacheKey($userSlug), $idolSlug);
    }
}
