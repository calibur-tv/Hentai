<?php

namespace App\Events\Tag;

use App\Models\Tag;
use App\User;
use Illuminate\Queue\SerializesModels;

class Update
{
    use SerializesModels;

    public $tag;
    public $user;
    public $isIdol;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Tag $tag, User $user, bool $isIdol)
    {
        $this->tag = $tag;
        $this->user = $user;
        $this->isIdol = $isIdol;
    }
}
