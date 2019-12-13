<?php

namespace App\Events\Bangumi;

use App\Models\Bangumi;
use App\User;
use Illuminate\Queue\SerializesModels;

class Pass
{
    use SerializesModels;

    public $user;
    public $bangumi;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Bangumi $bangumi)
    {
        $this->user = $user;
        $this->bangumi = $bangumi;
    }
}
