<?php

namespace App\Events\User;

use App\Events\ToggleEvent;
use App\User;
use Illuminate\Database\Eloquent\Model;

class ToggleFollowUser extends ToggleEvent
{
    public $followMe;

    public function __construct(User $target, User $user, bool $result)
    {
        parent::__construct($target, $user, $result);

        $this->followMe = $user->isFollowedBy($target);
    }
}
