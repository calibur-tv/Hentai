<?php


namespace App\Events\Message;

use App\Models\Message;
use App\User;
use Illuminate\Queue\SerializesModels;

class Create
{
    use SerializesModels;

    public $message;
    public $sender;
    public $roomId;

    public function __construct(Message $message, User $sender, string $roomId)
    {
        $this->message = $message;
        $this->sender = $sender;
        $this->roomId = $roomId;
    }
}
