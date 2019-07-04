<?php

namespace App\Events\User;

use App\Events\ToggleEvent;
use App\User;
use Illuminate\Database\Eloquent\Model;

class ToggleFollowUser extends ToggleEvent
{
    public $followMe;

    public function __construct(User $user, User $target, bool $result)
    {
        parent::__construct($user, $target, $result);

        $this->followMe = $user->isFollowedBy($target);
    }
}
