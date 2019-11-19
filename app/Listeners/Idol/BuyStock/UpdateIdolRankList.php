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
        $score = $event->coinAmount + $event->idol->market_price;
        $idolRepository = new IdolRepository();
        $idolRepository->SortAdd($idolRepository->idolIdsCacheKey('activity'), $slug);
        $idolRepository->SortAdd($idolRepository->idolIdsCacheKey('topped'), $slug, $score);
        if ($event->idol->is_newbie)
        {
            $idolRepository->SortAdd($idolRepository->idolIdsCacheKey('newbie'), $slug, $score);
        }
    }
}
