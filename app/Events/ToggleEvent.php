<?php

namespace App\Events;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class ToggleEvent
{
    use SerializesModels;

    public $user;
    public $target;
    public $result;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, User $target, bool $result)
    {
        $this->user = $user;
        $this->target = $target;
        $this->result = $result;
    }
}
