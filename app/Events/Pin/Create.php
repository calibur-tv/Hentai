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
    public $notebook;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Pin $pin, User $user, Tag $area, Tag $notebook)
    {
        $this->pin = $pin;
        $this->user = $user;
        $this->area = $area;
        $this->notebook = $notebook;
    }
}
