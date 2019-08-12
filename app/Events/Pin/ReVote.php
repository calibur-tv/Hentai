<?php


namespace App\Events\Pin;

use App\Models\Pin;
use App\User;
use Illuminate\Queue\SerializesModels;

class ReVote
{
    use SerializesModels;

    public $pin;
    public $user;
    public $answers;
    public $oldAnswer;

    public function __construct(Pin $pin, User $user, array $answers, array $oldAnswer)
    {
        $this->pin = $pin;
        $this->user = $user;
        $this->answers = $answers;
        $this->oldAnswer = $oldAnswer;
    }
}
