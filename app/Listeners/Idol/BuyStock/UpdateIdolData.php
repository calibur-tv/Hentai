<?php

namespace App\Listeners\Idol\BuyStock;

use App\Models\IdolFans;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateIdolData
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
        $event->idol->increment('coin_count', $event->coinAmount);
        $event->idol->increment('stock_count', $event->stockCount);

        if ($event->fansData)
        {
            $event->fansData->increment('coin_count', $event->coinAmount);
            $event->fansData->increment('stock_count', $event->stockCount);
        }
        else
        {
            $event->idol->increment('fans_count');

            IdolFans::create([
                'user_slug' => $event->user->slug,
                'idol_slug' => $event->idol->slug,
                'coin_count' => $event->coinAmount,
                'stock_count' => $event->stockCount
            ]);
        }
    }
}
