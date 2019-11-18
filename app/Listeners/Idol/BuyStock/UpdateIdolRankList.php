<?php

namespace App\Listeners\Idol\BuyStock;

use App\Http\Repositories\IdolRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateIdolRankList
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
        $slug = $event->idol->slug;

        $idolRepository = new IdolRepository();
        $idolRepository->SortAdd($idolRepository->idolIdsCacheKey('activity'), $slug);
        $idolRepository->SortAdd($idolRepository->idolIdsCacheKey('topped'), $event->coinAmount);
        if ($event->idol->is_newbie)
        {
            $idolRepository->SortAdd($idolRepository->idolIdsCacheKey('newbie'), $event->coinAmount);
        }
    }
}
