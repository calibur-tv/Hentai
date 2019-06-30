<?php

namespace App\Events\Pin;

use App\Models\Pin;
use Illuminate\Queue\SerializesModels;

class Update
{
    use SerializesModels;

    public $pin;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Pin $pin)
    {
        $this->pin = $pin;
    }
}
