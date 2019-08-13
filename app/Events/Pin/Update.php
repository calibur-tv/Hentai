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
    public $attachTags;
    public $detachTags;
    public $canMovePin;

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

        $oldTags = $pin
            ->tags()
            ->pluck('slug')
            ->toArray();

        $this->attachTags = array_diff($tags, $oldTags);
        $this->detachTags = array_diff($oldTags, $tags);
    }
}
