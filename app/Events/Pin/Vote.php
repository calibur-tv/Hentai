<?php


namespace App\Events\Pin;

use App\Models\Pin;
use App\User;
use Illuminate\Queue\SerializesModels;

class Vote
{
    use SerializesModels;

    public $pin;
    public $user;
    public $answers;

    public function __construct(Pin $pin, User $user, array $answers)
    {
        $this->pin = $pin;
        $this->user = $user;
        $this->answers = $answers;
    }
}
