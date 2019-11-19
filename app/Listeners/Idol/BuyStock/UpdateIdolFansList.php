<?php

namespace App\Listeners\Idol\BuyStock;

use App\Http\Repositories\IdolRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateIdolFansList
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

        $idolRepository = new IdolRepository();
        $idolRepository->SortAdd($idolRepository->idolFansCacheKey($idolSlug, 'activity'), $userSlug);
        if ($event->fansData)
        {
            $idolRepository->SortAdd($idolRepository->idolFansCacheKey($idolSlug, 'biggest'), $userSlug, $event->fansData->stock_count + $event->stockCount);
        }
        else
        {
            $idolRepository->SortAdd($idolRepository->idolFansCacheKey($idolSlug, 'biggest'), $userSlug, $event->stockCount);
        }
    }
}
