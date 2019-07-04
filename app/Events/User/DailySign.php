<?php

namespace App\Events\User;

use App\User;
use Illuminate\Queue\SerializesModels;

class DailySign
{
    use SerializesModels;

    public $user;
    public $activity;
    public $coin;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, int $activity, int $coin)
    {
        $this->user = $user;
        $this->activity = $activity;
        $this->coin = $coin;
    }
}
