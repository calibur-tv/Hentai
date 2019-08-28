<?php

namespace App\Events\Pin;

use App\Models\Pin;
use App\User;
use Illuminate\Queue\SerializesModels;

class Update
{
    use SerializesModels;

    public $pin;
    public $user;
    public $tags;
    public $doPublish;
    public $published;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Pin $pin, User $user, array $tags, bool $publish)
    {
        $this->pin = $pin;
        $this->user = $user;
        $this->tags = $tags;
        $this->doPublish = $publish;
        $this->published = !!$pin->published_at;
    }
}
