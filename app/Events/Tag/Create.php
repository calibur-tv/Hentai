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
    public $isIdol;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Tag $tag, $user, Tag $parent, bool $isIdol)
    {
        $this->tag = $tag;
        $this->user = $user;
        $this->parent = $parent;
        $this->isIdol = $isIdol;
    }
}
