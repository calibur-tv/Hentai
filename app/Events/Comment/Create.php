<?php

namespace App\Events\Comment;

use App\Models\Comment;
use App\User;
use Illuminate\Queue\SerializesModels;

class Create
{
    use SerializesModels;

    public $comment;
    public $author;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Comment $comment, User $author)
    {
        $this->comment = $comment;
        $this->author = $author;
    }
}
