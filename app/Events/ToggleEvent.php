<?php

namespace App\Events;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class ToggleEvent
{
    use SerializesModels;

    public $target;
    public $user;
    public $result;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $target, User $user, bool $result)
    {
        $this->target = $target;
        $this->user = $user;
        $this->result = $result;
    }
}
