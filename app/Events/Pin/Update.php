<?php

namespace App\Events\Pin;

use App\Models\Pin;
use App\User;
use Illuminate\Queue\SerializesModels;

class Update
{
    use SerializesModels;

    public $pin;
    public $user;
    public $publish;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Pin $pin, User $user, bool $publish)
    {
        $this->pin = $pin;
        $this->user = $user;
        $this->publish = $publish;
    }
}
