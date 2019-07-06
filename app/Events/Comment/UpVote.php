<?php

namespace App\Events\Comment;

use App\Models\Comment;
use App\User;
use Illuminate\Queue\SerializesModels;

class UpVote
{
    use SerializesModels;

    public $comment;
    public $user;
    public $result;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Comment $comment, User $user, bool $result)
    {
        $this->comment = $comment;
        $this->user = $user;
        $this->result = $result;
    }
}
