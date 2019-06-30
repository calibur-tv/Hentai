<?php

namespace App\Events\Pin;

use App\Models\Pin;
use App\User;
use Illuminate\Queue\SerializesModels;

class Delete
{
    use SerializesModels;

    public $pin;
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Pin $pin, User $user)
    {
        $this->pin = $pin;
        $this->user = $user;
    }
}
