<?php

namespace App\Events\User;

use App\User;
use Illuminate\Queue\SerializesModels;

class Register
{
    use SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
