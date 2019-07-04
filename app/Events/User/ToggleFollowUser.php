<?php

namespace App\Events\User;

use App\Events\ToggleEvent;
use App\User;
use Illuminate\Database\Eloquent\Model;

class ToggleFollowUser extends ToggleEvent
{
    public function __construct(User $user, User $target, Model $class, string $model, string $action, array $result)
    {
        parent::__construct($user, $target, $class, $model, $action, $result);
    }
}
