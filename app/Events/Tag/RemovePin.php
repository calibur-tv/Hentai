<?php

namespace App\Events\Tag;

use App\Models\Pin;
use App\Models\Tag;
use App\User;
use Illuminate\Queue\SerializesModels;

class RemovePin
{
    use SerializesModels;

    public $tag;
    public $pin;
    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Tag $tag, Pin $pin, User $user)
    {
        $this->tag = $tag;
        $this->pin = $pin;
        $this->user = $user;
    }
}
