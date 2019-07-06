<?php

namespace App\Events\Comment;

use App\Models\Comment;
use App\User;
use Illuminate\Queue\SerializesModels;

class Delete
{
    use SerializesModels;

    public $comment;
    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Comment $comment, User $user)
    {
        $this->comment = $comment;
        $this->user = $user;
    }
}
