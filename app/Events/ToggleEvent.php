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
    public $class;
    public $model;
    public $action;
    public $result;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, User $target, Model $class, string $model, string $action, array $result)
    {
        $this->user = $user;
        $this->target = $target;
        $this->class = $class;
        $this->model = $model;
        $this->action = $action;
        $this->result = $result;
    }
}
