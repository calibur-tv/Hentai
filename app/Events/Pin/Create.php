<?php

namespace App\Events\Pin;

use App\Models\Pin;
use App\Models\Tag;
use App\User;
use Illuminate\Queue\SerializesModels;

class Create
{
    use SerializesModels;

    public $pin;
    public $user;
    public $area;
    public $topic;
    public $notebook;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Pin $pin, User $user, $area, Tag $topic, Tag $notebook)
    {
        $this->pin = $pin;
        $this->user = $user;
        $this->area = $area;
        $this->topic = $topic;
        $this->notebook = $notebook;
    }
}
