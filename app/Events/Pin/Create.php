<?php

namespace App\Events\Pin;

use App\Models\Pin;
use App\Models\Tag;
use Illuminate\Queue\SerializesModels;

class Create
{
    use SerializesModels;

    public $pin;
    public $area;
    public $notebook;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Pin $pin, Tag $area, Tag $notebook)
    {
        $this->pin = $pin;
        $this->area = $area;
        $this->notebook = $notebook;
    }
}
