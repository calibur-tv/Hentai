<?php

namespace App\Events\Idol;

use App\Models\Idol;
use App\Models\IdolFans;
use App\User;
use Illuminate\Queue\SerializesModels;

class BuyStock
{
    use SerializesModels;

    public $user;
    public $idol;
    public $coinAmount;
    public $stockCount;
    public $fansData;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Idol $idol, $coinAmount, $stockCount)
    {
        $this->user = $user;
        $this->idol = $idol;
        $this->coinAmount = $coinAmount;
        $this->stockCount = $stockCount;
        $this->fansData = IdolFans
            ::where('user_slug', $user->slug)
            ->where('idol_slug', $idol->slug)
            ->first();
    }
}
