<?php

namespace App\Events\Idol;

use App\Models\Idol;
use App\User;
use Illuminate\Queue\SerializesModels;

class BuyStock
{
    use SerializesModels;

    public $user;
    public $idol;
    public $amount;
    public $count;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Idol $idol, $amount, $count)
    {
        $this->user = $user;
        $this->idol = $idol;
        $this->amount = $amount;
        $this->count = $count;
    }
}
