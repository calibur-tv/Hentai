<?php


namespace App\Events\Pin;


use App\Models\Pin;
use App\User;
use Illuminate\Queue\SerializesModels;

class Move
{
    use SerializesModels;

    public $pin;
    public $user;
    public $tags;

    public function __construct(Pin $pin, User $user, array $tags)
    {
        $this->pin = $pin;
        $this->user = $user;
        $this->tags = $tags;
    }
}
