<?php

namespace App\Events\Tag;

use App\Models\Tag;
use App\User;
use Illuminate\Queue\SerializesModels;

class Create
{
    use SerializesModels;

    public $tag;
    public $user;
    public $parent;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Tag $tag, User $user, Tag $parent)
    {
        $this->tag = $tag;
        $this->user = $user;
        $this->parent = $parent;
    }
}
