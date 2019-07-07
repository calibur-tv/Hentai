<?php

namespace App\Events\Pin;

use App\Models\Pin;
use App\User;
use Illuminate\Queue\SerializesModels;

class UpVote
{
    use SerializesModels;

    public $pin;
    public $user;
    public $result;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Pin $pin, User $user, bool $result)
    {
        $this->pin = $pin;
        $this->user = $user;
        $this->result = $result;
    }
}
